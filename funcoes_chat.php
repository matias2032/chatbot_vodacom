<?php
// ============================================================
//  FUNCOES_CHAT.PHP — Lógica RAG + Gemini partilhada
//  Usado por: api_chat.php (web) e webhook.php (WhatsApp)
// ============================================================
// NÃO executa código ao ser incluído — só define funções.
// Requer: configuracao.php e conexao.php já incluídos antes.
// ============================================================


// ------------------------------------------------------------
// extrairPalavrasChave()
// Remove stopwords e devolve array de palavras relevantes
// ------------------------------------------------------------
function extrairPalavrasChave(string $texto): array {
    $stopwords = [
        'o','a','os','as','um','uma','uns','umas','de','do','da','dos','das',
        'em','no','na','nos','nas','por','para','com','sem','que','se','e',
        'é','ao','aos','à','às','pelo','pela','pelos','pelas','me','te','lhe',
        'eu','tu','ele','ela','nós','vós','eles','elas','isso','este','esta',
        'esse','essa','aquele','aquela','como','mais','mas','ou','já','foi',
        'ser','ter','haver','estar','fazer','dizer','ir','ver','dar','saber',
        'querer','poder','dever','qual','quais','quando','onde','quem','quanto',
        'diz','faz','tem','vai','vem','seu','sua','seus','suas','meu','minha',
    ];
    $palavras = preg_split('/[\s\-_,;.!?()\[\]{}:\/\\\\]+/', mb_strtolower(trim($texto)));
    return array_values(array_filter(
        $palavras,
        fn($p) => mb_strlen($p) >= 3 && !in_array($p, $stopwords)
    ));
}


// ------------------------------------------------------------
// buscarContexto()
// Executa o pipeline RAG e devolve array com:
//   'partes'  => string[] — blocos de texto para o prompt
//   'fontes'  => array[]  — [tipo, id] para registar em BD
// ------------------------------------------------------------
function buscarContexto(PDO $pdo, string $mensagem): array {
    $contexto_partes = [];
    $fontes_usadas   = [];

    // ── 3a. BASE DE CONHECIMENTO ─────────────────────────────

    // Tentativa 1: FTS português
    $stmt = $pdo->prepare("
        SELECT id_base_conhecimento, titulo, conteudo,
               ts_rank(
                   to_tsvector('portuguese', titulo || ' ' || conteudo),
                   plainto_tsquery('portuguese', :q)
               ) AS r
        FROM base_conhecimento
        WHERE id_configuracao_bot = :bot AND ativo = TRUE
          AND to_tsvector('portuguese', titulo || ' ' || conteudo)
              @@ plainto_tsquery('portuguese', :q2)
        ORDER BY r DESC LIMIT :lim
    ");
    $stmt->execute([':bot' => BOT_ID, ':q' => $mensagem, ':q2' => $mensagem, ':lim' => MAX_RESULTADOS_BUSCA]);
    $conhecimentos = $stmt->fetchAll();

    // Tentativa 2: ILIKE por palavras-chave
    if (empty($conhecimentos)) {
        $palavras = array_slice(extrairPalavrasChave($mensagem), 0, 5);
        if (!empty($palavras)) {
            $conds  = [];
            $params = [':bot' => BOT_ID];
            foreach ($palavras as $i => $p) {
                $conds[]         = "(titulo ILIKE :p{$i} OR conteudo ILIKE :p{$i})";
                $params[":p{$i}"] = "%{$p}%";
            }
            $stmt = $pdo->prepare(
                "SELECT id_base_conhecimento, titulo, conteudo, 1.0 AS r
                 FROM base_conhecimento
                 WHERE id_configuracao_bot = :bot AND ativo = TRUE
                   AND (" . implode(' OR ', $conds) . ")
                 LIMIT " . MAX_RESULTADOS_BUSCA
            );
            $stmt->execute($params);
            $conhecimentos = $stmt->fetchAll();
        }
    }

    // Tentativa 3: fallback por prioridade
    if (empty($conhecimentos)) {
        $stmt = $pdo->prepare("
            SELECT id_base_conhecimento, titulo, conteudo, prioridade AS r
            FROM base_conhecimento
            WHERE id_configuracao_bot = :bot AND ativo = TRUE
            ORDER BY prioridade ASC LIMIT 3
        ");
        $stmt->execute([':bot' => BOT_ID]);
        $conhecimentos = $stmt->fetchAll();
    }

    foreach ($conhecimentos as $k) {
        $contexto_partes[] = "### {$k['titulo']}\n{$k['conteudo']}";
        $fontes_usadas[]   = ['tipo' => 'conhecimento', 'id' => $k['id_base_conhecimento']];
    }

    // ── 3b. FRAGMENTOS DE DOCUMENTOS ─────────────────────────
    $restantes = MAX_RESULTADOS_BUSCA - count($contexto_partes);

    if ($restantes > 0) {
        $fragmentos = [];

        // Estratégia 1: websearch_to_tsquery
        try {
            $stmt = $pdo->prepare("
                SELECT f.id_fragmento, f.conteudo, d.nome_original,
                       ts_rank(
                           to_tsvector('portuguese', f.conteudo),
                           websearch_to_tsquery('portuguese', :q)
                       ) AS r
                FROM fragmentos_documento f
                JOIN documentos d ON d.id_documento = f.id_documento
                WHERE d.id_configuracao_bot = :bot
                  AND d.estado = 'pronto'
                  AND to_tsvector('portuguese', f.conteudo)
                      @@ websearch_to_tsquery('portuguese', :q2)
                ORDER BY r DESC LIMIT :lim
            ");
            $stmt->execute([':bot' => BOT_ID, ':q' => $mensagem, ':q2' => $mensagem, ':lim' => $restantes]);
            $fragmentos = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("[RAG] websearch_to_tsquery falhou: " . $e->getMessage());
        }

        // Estratégia 2: plainto_tsquery
        if (empty($fragmentos)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT f.id_fragmento, f.conteudo, d.nome_original,
                           ts_rank(
                               to_tsvector('portuguese', f.conteudo),
                               plainto_tsquery('portuguese', :q)
                           ) AS r
                    FROM fragmentos_documento f
                    JOIN documentos d ON d.id_documento = f.id_documento
                    WHERE d.id_configuracao_bot = :bot
                      AND d.estado = 'pronto'
                      AND to_tsvector('portuguese', f.conteudo)
                          @@ plainto_tsquery('portuguese', :q2)
                    ORDER BY r DESC LIMIT :lim
                ");
                $stmt->execute([':bot' => BOT_ID, ':q' => $mensagem, ':q2' => $mensagem, ':lim' => $restantes]);
                $fragmentos = $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("[RAG] plainto_tsquery falhou: " . $e->getMessage());
            }
        }

        // Estratégia 3: ILIKE por palavras-chave
        if (empty($fragmentos)) {
            $palavras = array_slice(extrairPalavrasChave($mensagem), 0, 6);
            if (!empty($palavras)) {
                $conds  = [];
                $params = [':bot' => BOT_ID];
                foreach ($palavras as $i => $p) {
                    $conds[]         = "f.conteudo ILIKE :p{$i}";
                    $params[":p{$i}"] = "%{$p}%";
                }
                $stmt = $pdo->prepare(
                    "SELECT f.id_fragmento, f.conteudo, d.nome_original, 1.0 AS r
                     FROM fragmentos_documento f
                     JOIN documentos d ON d.id_documento = f.id_documento
                     WHERE d.id_configuracao_bot = :bot
                       AND d.estado = 'pronto'
                       AND (" . implode(' OR ', $conds) . ")
                     ORDER BY f.indice_fragmento ASC
                     LIMIT " . (int)$restantes
                );
                $stmt->execute($params);
                $fragmentos = $stmt->fetchAll();
            }
        }

        // Estratégia 4: primeiros fragmentos dos documentos mais recentes
        if (empty($fragmentos)) {
            $stmt = $pdo->prepare("
                SELECT f.id_fragmento, f.conteudo, d.nome_original, 0.1 AS r
                FROM fragmentos_documento f
                JOIN documentos d ON d.id_documento = f.id_documento
                WHERE d.id_configuracao_bot = :bot AND d.estado = 'pronto'
                ORDER BY d.processado_em DESC, f.indice_fragmento ASC
                LIMIT :lim
            ");
            $stmt->execute([':bot' => BOT_ID, ':lim' => $restantes]);
            $fragmentos = $stmt->fetchAll();
        }

        foreach ($fragmentos as $f) {
            $contexto_partes[] = "### Documento: {$f['nome_original']}\n{$f['conteudo']}";
            $fontes_usadas[]   = ['tipo' => 'fragmento', 'id' => $f['id_fragmento']];
        }

        error_log("[RAG] Mensagem: '{$mensagem}' | Fragmentos: " . count($fragmentos));
    }

    return ['partes' => $contexto_partes, 'fontes' => $fontes_usadas];
}


// ------------------------------------------------------------
// montarPromptSistema()
// Constrói o prompt de sistema com identidade + perfil + contexto
// ------------------------------------------------------------
function montarPromptSistema(PDO $pdo, array $contexto_partes): array {
    $stmt = $pdo->prepare("
        SELECT p.*,
               EXTRACT(YEAR FROM AGE(p.data_nascimento))::INTEGER AS idade,
               b.nome AS nome_bot,
               b.prompt_sistema
        FROM perfil_criador p
        JOIN configuracao_bot b ON b.id_configuracao_bot = p.id_configuracao_bot
        WHERE p.id_configuracao_bot = :bot
        LIMIT 1
    ");
    $stmt->execute([':bot' => BOT_ID]);
    $perfil = $stmt->fetch();

    $nome_bot_str     = $perfil['nome_bot']      ?? 'MeuBot';
    $nome_criador_str = $perfil['nome_completo'] ?? 'o teu criador';

    $prompt  = "### IDENTIDADE (OBRIGATÓRIO)\n";
    $prompt .= "1. O teu nome é {$nome_bot_str}.\n";
    $prompt .= "2. Foste integralmente criado por {$nome_criador_str}.\n";
    $prompt .= "3. Nunca digas que és produto da Google, Anthropic ou outro fornecedor.\n\n";
    $prompt .= $perfil['prompt_sistema'] ?? '';

    if ($perfil) {
        $idade_txt = $perfil['idade'] ? "{$perfil['idade']} anos" : 'não informada';
        $prompt   .= "\n\n## Dados do Criador\n"
            . "- Nome: {$perfil['nome_completo']}\n"
            . "- Data de nascimento: {$perfil['data_nascimento']}\n"
            . "- Idade: {$idade_txt}\n"
            . "- Telefone: {$perfil['telefone']}\n"
            . "- Morada: {$perfil['morada']}\n"
            . "- Email: {$perfil['email']}\n"
            . "- Profissão: {$perfil['profissao']}\n"
            . "- Nacionalidade: {$perfil['nacionalidade']}\n"
            . "- Bio: {$perfil['bio']}\n";
    }

    if (!empty($contexto_partes)) {
        $prompt .= "\n\n## Base de Conhecimento\n"
            . "Usa OBRIGATORIAMENTE as informações abaixo para responder. "
            . "Se a resposta estiver aqui, usa-a directamente. "
            . "Se não estiver, diz honestamente que não tens essa informação.\n\n"
            . implode("\n\n---\n\n", $contexto_partes);
    } else {
        $prompt .= "\n\n## Base de Conhecimento\n"
            . "Não foi encontrado contexto relevante. "
            . "Responde de forma geral sendo honesto sobre as limitações.";
    }

    return ['prompt' => $prompt, 'perfil' => $perfil];
}


// ------------------------------------------------------------
// chamarGemini()
// Chama a API Gemini com histórico + contexto e devolve array:
//   'texto'      => string  — resposta do modelo
//   't_entrada'  => int|null
//   't_saida'    => int|null
//   'tempo_ms'   => int
//   'erro'       => string|null — null se tudo correu bem
// ------------------------------------------------------------
function chamarGemini(string $mensagem, array $contexto, string $id_conversa, PDO $pdo, ?string $id_msg_excluir = null): array {
    // Monta prompt de sistema
    ['prompt' => $prompt_sistema] = montarPromptSistema($pdo, $contexto['partes']);

    // Histórico recente
    $sql = "SELECT papel, conteudo FROM mensagens
            WHERE id_conversa = :c";
    $params = [':c' => $id_conversa];

    if ($id_msg_excluir !== null) {
        $sql .= " AND id_mensagem != :id";
        $params[':id'] = $id_msg_excluir;
    }

    $sql .= " ORDER BY enviada_em DESC LIMIT :lim";
    $params[':lim'] = MAX_HISTORICO_MENSAGENS;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $historico = array_reverse($stmt->fetchAll());

    // Monta contents
    $contents = [];
    foreach ($historico as $msg) {
        $contents[] = [
            'role'  => $msg['papel'] === 'utilizador' ? 'user' : 'model',
            'parts' => [['text' => $msg['conteudo']]],
        ];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => $mensagem]]];

    $payload = [
        'system_instruction' => ['parts' => [['text' => $prompt_sistema]]],
        'contents'           => $contents,
        'generationConfig'   => ['maxOutputTokens' => 1024, 'temperature' => 0.7],
    ];

    // cURL
    $url = GEMINI_API_URL . '?key=' . GEMINI_CHAVE_API;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $inicio    = microtime(true);
    $raw       = curl_exec($ch);
    $tempo_ms  = (int)((microtime(true) - $inicio) * 1000);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $http_code !== 200) {
        return [
            'texto'     => 'Não consegui gerar uma resposta. Tenta novamente.',
            't_entrada' => null,
            't_saida'   => null,
            'tempo_ms'  => $tempo_ms,
            'erro'      => "Gemini HTTP {$http_code}: " . ($raw ?: 'Sem resposta'),
        ];
    }

    $dados = json_decode($raw, true);
    return [
        'texto'     => $dados['candidates'][0]['content']['parts'][0]['text']
                       ?? 'Não consegui gerar uma resposta. Tenta novamente.',
        't_entrada' => $dados['usageMetadata']['promptTokenCount']     ?? null,
        't_saida'   => $dados['usageMetadata']['candidatesTokenCount'] ?? null,
        'tempo_ms'  => $tempo_ms,
        'erro'      => null,
    ];
}


// ------------------------------------------------------------
// registarFontes()
// Guarda na BD as fontes RAG usadas para uma mensagem
// ------------------------------------------------------------
function registarFontes(PDO $pdo, string $id_mensagem, array $fontes): void {
    $stmt = $pdo->prepare("
        INSERT INTO fontes_mensagem (id_mensagem, id_base_conhecimento, id_fragmento)
        VALUES (:m, :k, :f)
    ");
    foreach ($fontes as $fonte) {
        $stmt->execute([
            ':m' => $id_mensagem,
            ':k' => $fonte['tipo'] === 'conhecimento' ? $fonte['id'] : null,
            ':f' => $fonte['tipo'] === 'fragmento'    ? $fonte['id'] : null,
        ]);
    }
}
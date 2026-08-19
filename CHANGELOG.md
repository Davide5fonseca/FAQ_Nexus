# Registo de alterações

Todas as alterações relevantes a esta aplicação ficam registadas aqui.
Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/);
versões segundo [Semantic Versioning](https://semver.org/lang/pt-BR/) (MAIOR.MENOR.CORRECÇÃO).

Como usar: sempre que se altere a aplicação, acrescentar uma linha em
**[Por lançar]**. Quando se instala uma nova versão no servidor, muda-se
"[Por lançar]" para o número da versão e a data, e abre-se uma nova secção
"[Por lançar]" vazia por cima.

## [Por lançar]

### Adicionado
- Configuração Apache para servir a aplicação numa sub-pasta
  (`deploy/apache-subpasta.conf`), usada no servidor interno da Nexus.
- Secção no README sobre a instalação real (servidor 192.168.1.69,
  `https://infra.nexus-solutions.pt:9443/procedimentos`) e como actualizar.

### Alterado
- Documentado que, em sub-pasta, não se deve usar `route:cache`
  (provoca erro 405 na página inicial).

## [1.0.0] — 2026-08-19

Primeira versão da Base de Procedimentos Técnicos (Nexus Solutions).

### Adicionado
- Consulta pública de procedimentos com pesquisa por texto (título, passos,
  campos, categoria), filtro por categoria e por nível de intervenção.
- Cartões expansíveis com passos numerados, "o que registar no ticket",
  "quando escalar", referência, categoria, nível e data/autor da última alteração.
- Regras de segurança no topo da página de consulta.
- Impressão em A4 (um procedimento por página) da lista filtrada ou de um
  único procedimento; regras de segurança na primeira página.
- Área de administração com autenticação por email e palavra-passe.
- Procedimentos: criar, editar, duplicar, arquivar/desarquivar e apagar;
  referência automática `PROC-01`, `PROC-02`… que nunca é reutilizada.
- Editor de passos com reordenação (botões ↑↓, Alt+↑/↓, arrastar e largar).
- Gestão de categorias (com protecção contra apagar categorias em uso).
- Gestão de regras de segurança com reordenação.
- Estados vazios em todas as listas ("Ainda não há procedimentos. Criar o primeiro.").
- Comandos de terminal `app:criar-admin` e `app:alterar-password`.
- Mensagens, validações e páginas de erro em português de Portugal.
- Interface responsiva (telemóvel/tablet/computador) e acessível
  (navegação por teclado, foco visível, contraste AA, leitores de ecrã).
- Script de instalação para Ubuntu (`deploy/instalar.sh`) com Nginx, PHP-FPM,
  PostgreSQL e HTTPS automático (Certbot).
- Cópias de segurança diárias da base de dados (`deploy/backup.sh`).
- 20 testes automáticos de funcionalidade.

### Segurança
- Palavras-passe guardadas com *hash* (bcrypt).
- Sessões com expiração (8 horas sem actividade) e cookies seguros em produção.
- Limite de 5 tentativas de entrada por minuto.
- Protecção CSRF em todos os formulários.

[Por lançar]: https://github.com/Davide5fonseca/FAQ_Nexus/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Davide5fonseca/FAQ_Nexus/releases/tag/v1.0.0

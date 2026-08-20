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
- Caixa **"Manter sessão iniciada"** na entrada, válida 30 dias (o Laravel usa
  400 por omissão, o que era demasiado para conteúdo interno).

### Segurança
- Quem ficar com a **conta desactivada** perde a sessão no pedido seguinte, mesmo
  tendo marcado "manter sessão iniciada" — esse caminho não passava pela
  validação do login e deixava entrar contas já desactivadas.
- Mudar a palavra-passe (na recuperação ou pela administração) passa também a
  invalidar o "manter sessão iniciada" em todos os dispositivos.

### Alterado
- **Página de entrada refeita**: cartão centrado no ecrã (deixa de ficar encostado
  ao topo), marca por cima do cartão, e sem a barra de navegação — os links
  "Consulta" e "Entrar" não faziam sentido aqui, já que a consulta exige sessão.
  Botão "Entrar" a toda a largura e um **olhinho** dentro do campo para mostrar
  ou ocultar a palavra-passe.
  As páginas de recuperar e definir palavra-passe seguem o mesmo aspecto.
- O botão **"Apagar"** passa a chamar-se **"Eliminar"** em toda a aplicação
  (procedimentos, categorias, regras e contas), com as mensagens a condizer.
- Na lista de administração, o botão **"Arquivar"** dá lugar a **"Editar"**.
  Arquivar, desarquivar e apagar continuam disponíveis dentro da página de
  edição, em "Outras acções".
- Botão dos emails corrigido para o **Outlook**, que ignora o espaçamento dentro
  de links: o espaçamento passou para a célula da tabela e os cantos arredondados
  são feitos com VML. Antes aparecia um rectângulo com o texto encostado.
- **Emails com a identidade da Nexus**, no mesmo formato da Nexus Ops: cartão
  branco com faixa verde no topo, marca, botão verde legível e endereço de
  recurso em baixo. Substitui o modelo genérico do Laravel, em que o botão
  aparecia preto com texto ilegível. Inclui versão em texto simples.
- A aplicação passa a chamar-se **Knowledgebase Nexus** (separador do browser,
  assunto dos emails e cabeçalho das folhas impressas). O nome vem de `APP_NAME`
  no `.env`, por isso muda-se num único sítio.

### Adicionado
- **Separação por área**: cada procedimento pertence à *Área técnica* ou à
  *Produção*. Cada pessoa só vê, pesquisa, imprime e edita os da sua área; os
  administradores vêem todas. O bloqueio é feito no servidor — o acesso directo
  ao endereço de um procedimento de outra área devolve 403.
- Todo o conteúdo já existente ficou na **Área técnica**.
- No formulário, o administrador escolhe a área; quem não é administrador cria
  sempre na sua. A lista de administração mostra a coluna "Área" ao administrador.
- Na consulta, o filtro de categorias só mostra as que têm procedimentos visíveis.

### Removido
- **Arquivar / desarquivar**, por completo: botões, rotas, código e a coluna
  `archived_at` na base de dados. Um procedimento existe ou é eliminado.
- Texto **"Base de Procedimentos Técnicos"** ao lado do logótipo: o cabeçalho fica
  só com a marca. O nome mantém-se no separador do browser.
- Filtro **"Estado"** na lista de administração. A lista passa a mostrar activos e
  arquivados em conjunto, com os arquivados marcados por etiqueta — assim
  continuam acessíveis para se poderem desarquivar.
- Função **"Duplicar"** procedimento: botões, rota e código. Deixou de fazer
  sentido no fluxo de trabalho.

### Alterado
- **Logótipo oficial**: passa a usar o mesmo ficheiro da Nexus Ops
  (`public/img/nexus-1.png`), em vez da reprodução em SVG feita à mão. O ícone do
  separador do browser passa também a ser o oficial. "Technical Suite" fica como
  subtítulo por baixo da marca.
- **Limpeza de texto em todas as páginas**: retirados subtítulos explicativos,
  legendas de perfis, textos de ajuda redundantes e informação repetida (a caixa
  "Ficha" na consulta repetia referência, categoria e data já visíveis; o botão
  "Editar" na lista de administração repetia a ligação do título). A coluna
  "Alterado" mostra só a data, com o detalhe na dica do rato, e a contagem de
  resultados só aparece quando há um filtro activo.

### Adicionado
- **Perfil "Leitor"**: só consulta e imprime procedimentos. Não vê a área de
  administração (o link nem aparece) nem os botões de criar/editar, e qualquer
  tentativa de aceder a `/admin` devolve 403.
- **Login obrigatório em toda a aplicação**: a consulta deixou de ser aberta;
  qualquer página exige sessão iniciada (é conteúdo interno num servidor acessível
  pela internet).
- **Convite por email**: o administrador cria a conta apenas com nome, email, área
  e perfil — a pessoa recebe um email com um link para **definir a própria
  palavra-passe** (válido 3 dias). O administrador nunca vê nem escolhe a palavra-passe.
- Botão **"Enviar convite"** na lista de utilizadores, para reenviar o link.
- **"Esqueci-me da palavra-passe"** na página de entrada, com email de recuperação.
  A resposta é sempre igual, para não revelar se um email tem conta.
- Envio de email via **Microsoft Graph** (mesma conta Suporte@nxs.pt e app do Entra ID
  já usada pela Nexus Ops), com transporte próprio em `app/Mail/Transport/GraphTransport.php`.


### Removido
- **Nível de intervenção** (1/2/3): retirado dos procedimentos, da consulta,
  dos filtros, do formulário, da impressão e da base de dados (coluna `level`).


### Adicionado
- **Gestão de utilizadores** na administração: o administrador cria, edita,
  desactiva e apaga contas pela interface. Cada conta tem **área** (Área técnica
  ou Produção) e **perfil** (Administrador ou Editor) — alinhado com o pedido de
  o portal ser alimentado pela área técnica e pela produção.
- Perfil **Editor**: cria, edita, duplica e arquiva procedimentos; não gere
  categorias, regras nem contas, e não apaga definitivamente.
- Campo **"Problema / sintomas"** nos procedimentos, mostrado antes da solução
  na consulta e na impressão, e incluído na pesquisa.
- "Criado por / alterado por" passa a registar nome e área, ex.: "Ana (Produção)".
- Contas desactivadas não conseguem entrar; protecção contra ficar sem
  administrador activo.
- Logótipo "Nexus Technical Suite" (SVG) na barra superior e novo ícone de separador.
- Configuração Apache para servir a aplicação numa sub-pasta
  (`deploy/apache-subpasta.conf`), usada no servidor interno da Nexus.
- Secção no README sobre a instalação real (servidor 192.168.1.69,
  `https://infra.nexus-solutions.pt:9443/procedimentos`) e como actualizar.

### Alterado
- Redesenho visual: faixa de destaque com pesquisa integrada e contadores, cartões
  com barra de nível e conteúdo em duas colunas (solução à esquerda; "registar no
  ticket", "quando escalar" e ficha à direita), estados vazios com ícone, página
  de entrada sobre fundo escuro, tipografia Inter (opcional, com fallback), rodapé
  escuro, ícones nos botões e alertas.
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

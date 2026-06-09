# FoodToGo

## Tema

P17 - Encomenda de Alimentos

## Descrição da aplicação

A FoodToGo é uma aplicação Web de encomenda de alimentos online que tem como objetivo ligar clientes e restaurantes através de uma plataforma simples, organizada e intuitiva.

A aplicação permite que visitantes consultem os restaurantes disponíveis e visualizem os respetivos menus sem necessidade de criar conta. No entanto, para realizar encomendas, alterar quantidades, confirmar pedidos e acompanhar o estado da encomenda, o utilizador deve criar conta ou iniciar sessão como cliente.

O cliente autenticado pode consultar restaurantes e menus, escolher produtos, definir quantidades, preencher dados de entrega/contacto, confirmar encomendas e consultar o histórico dos seus pedidos.

O restaurante autenticado tem acesso a um painel próprio, onde pode gerir os produtos do seu menu, atualizar preços, marcar produtos como disponíveis ou indisponíveis, consultar encomendas recebidas e atualizar o estado dessas encomendas.

O administrador é responsável pela gestão geral da plataforma. Compete ao administrador gerir utilizadores, criar e editar restaurantes, associar utilizadores a restaurantes, ativar/inativar restaurantes e consultar as encomendas realizadas na plataforma.

Na versão funcional atual, a criação e associação de restaurantes é controlada pelo administrador. Um utilizador só passa a ter perfil de restaurante quando é associado a um restaurante pelo administrador. Após essa associação, esse utilizador não volta diretamente a cliente; caso o restaurante deixe de operar, deve ser colocado como inativo.

A gestão de categorias de produtos foi prevista inicialmente como funcionalidade de baixa prioridade, mas na versão funcional atual foi simplificada. A organização dos menus é feita através da categoria do restaurante e da gestão direta dos produtos pelo painel do restaurante.

---

## Atores

* Visitante
* Cliente
* Restaurante
* Administrador

---

## Funcionalidades principais

### Visitante

* Visualizar restaurantes disponíveis;
* Visualizar menus dos restaurantes;
* Criar conta;
* Iniciar sessão.

### Cliente

* Visualizar restaurantes ativos;
* Visualizar menus;
* Escolher produtos de um restaurante;
* Alterar quantidades da encomenda;
* Confirmar encomenda;
* Indicar morada de entrega;
* Indicar contacto telefónico;
* Consultar histórico de encomendas;
* Acompanhar o estado das encomendas.

### Restaurante

* Iniciar sessão;
* Visualizar o restaurante associado à sua conta;
* Adicionar produtos ao menu;
* Editar produtos existentes;
* Atualizar preços;
* Atualizar disponibilidade dos produtos;
* Remover produtos quando possível;
* Marcar produtos como indisponíveis quando já existem encomendas associadas;
* Consultar encomendas recebidas;
* Ver dados do cliente, contacto, morada e observações;
* Atualizar estado das encomendas.

O restaurante não altera diretamente os dados principais do restaurante, como nome, morada, categoria ou estado ativo/inativo. Essa gestão pertence ao administrador.

### Administrador

* Iniciar sessão no painel de administração;
* Gerir utilizadores;
* Criar restaurantes;
* Editar dados dos restaurantes;
* Associar utilizadores a restaurantes;
* Ativar ou inativar restaurantes;
* Impedir associação de dois restaurantes ao mesmo utilizador;
* Impedir que um utilizador associado a restaurante volte diretamente a cliente;
* Consultar encomendas da plataforma.

---

## Regras principais da aplicação

### Visitante

O visitante pode consultar restaurantes e menus, mas não pode realizar encomendas. Para encomendar, deve iniciar sessão como cliente.

### Cliente

O cliente pode fazer encomendas apenas após autenticação. Cada encomenda fica associada ao seu perfil, permitindo consultar histórico e acompanhar o estado do pedido.

### Restaurante

O utilizador restaurante só existe como tal após associação feita pelo administrador. O restaurante gere apenas os produtos do menu e as encomendas recebidas.

### Administrador

O administrador controla a criação e associação dos restaurantes. Cada restaurante pode ter no máximo um utilizador associado, e cada utilizador só pode estar associado a um restaurante.

Se um restaurante deixar de operar, deve ser colocado como inativo. Dessa forma, deixa de aparecer para clientes e visitantes, mas o histórico de encomendas é preservado.

---

## User Stories

### Visitante

**VIS01 – Visualizar restaurantes**
Como Visitante, quero visualizar os restaurantes disponíveis na aplicação para conhecer as opções existentes na plataforma.
Prioridade: Alta

**VIS02 – Visualizar menu**
Como Visitante, quero visualizar o menu do restaurante escolhido para consultar os produtos disponíveis antes de criar conta.
Prioridade: Alta

**VIS03 – Criar conta**
Como Visitante, quero criar uma conta para poder realizar encomendas na plataforma.
Prioridade: Média

**VIS04 – Iniciar sessão**
Como Visitante, quero iniciar sessão para aceder às funcionalidades reservadas a utilizadores registados.
Prioridade: Alta

---

### Cliente

**CLI01 – Adicionar produtos ao carrinho/encomenda**
Como Cliente, quero escolher produtos de um restaurante para preparar a minha encomenda.
Prioridade: Alta

**CLI02 – Alterar carrinho/encomenda**
Como Cliente, quero alterar quantidades ou remover produtos antes de confirmar a encomenda.
Prioridade: Média

**CLI03 – Confirmar encomenda**
Como Cliente, quero confirmar a encomenda para que o restaurante a receba.
Prioridade: Alta

**CLI04 – Acompanhar estado da encomenda**
Como Cliente, quero acompanhar o estado da encomenda para saber se foi recebida, está em preparação, concluída ou cancelada.
Prioridade: Média

**CLI05 – Consultar histórico de encomendas**
Como Cliente, quero consultar o histórico das minhas encomendas para rever pedidos anteriores.
Prioridade: Baixa

---

### Restaurante

**RES01 – Aceder a restaurante associado**
Como Restaurante, quero aceder ao restaurante associado à minha conta para poder gerir o seu menu e as encomendas recebidas.
Prioridade: Alta

**RES02 – Iniciar sessão**
Como Restaurante, quero iniciar sessão na aplicação para gerir o meu restaurante.
Prioridade: Alta

**RES03 – Gerir menu**
Como Restaurante, quero visualizar e gerir o menu para controlar os produtos disponíveis.
Prioridade: Alta

**RES04 – Gerir preços e produtos**
Como Restaurante, quero adicionar, remover ou editar produtos e preços do menu.
Prioridade: Alta

**RES05 – Atualizar disponibilidade**
Como Restaurante, quero marcar produtos como disponíveis ou indisponíveis para evitar encomendas inválidas.
Prioridade: Média

**RES06 – Gerir encomendas**
Como Restaurante, quero visualizar encomendas recebidas para as poder processar.
Prioridade: Alta

**RES07 – Atualizar estado da encomenda**
Como Restaurante, quero atualizar o estado da encomenda para informar o cliente sobre o progresso do pedido.
Prioridade: Alta

**RES08 – Suspender operação do restaurante**
Como Restaurante, quero que o restaurante possa deixar de estar disponível caso deixe de operar.
Prioridade: Baixa

Nota: na versão funcional atual, a suspensão/reativação do restaurante é feita pelo administrador através do estado ativo/inativo, preservando o histórico das encomendas.

---

### Administrador

**ADM01 – Iniciar sessão**
Como Administrador, quero iniciar sessão no painel de administração para gerir a plataforma.
Prioridade: Alta

**ADM02 – Gerir restaurantes**
Como Administrador, quero criar, editar, ativar, inativar ou remover restaurantes para controlar quem pode vender na plataforma.
Prioridade: Alta

**ADM03 – Gerir utilizadores**
Como Administrador, quero consultar e gerir contas de clientes, restaurantes e administradores para manter a plataforma organizada.
Prioridade: Média

**ADM04 – Consultar encomendas da plataforma**
Como Administrador, quero consultar as encomendas realizadas para acompanhar o funcionamento geral do sistema.
Prioridade: Média

**ADM05 – Associar utilizadores a restaurantes**
Como Administrador, quero associar um utilizador a um restaurante para definir quem pode gerir esse restaurante.
Prioridade: Alta

---

## Estrutura principal de páginas

A aplicação foi organizada em páginas principais:

* `index.php` — página inicial;
* `login.html` — login e registo;
* `restaurantes.php` — consulta de restaurantes e menus;
* `carrinho.php` — escolha de produtos, confirmação e histórico de encomendas;
* `painelrestaurante.php` — gestão de menu e encomendas pelo restaurante;
* `paineladmin.php` — gestão de utilizadores, restaurantes e encomendas pelo administrador.

Alguns ficheiros `.html` adicionais existem apenas como apoio/redirecionamento ou referência da fase inicial do projeto, uma vez que a versão funcional passou a usar páginas PHP para permitir sessões, permissões e ligação à base de dados.

---

## Tecnologias utilizadas

* HTML;
* CSS;
* JavaScript;
* PHP;
* SQLite;
* JSON dinâmico em respostas PHP para comunicação com JavaScript.

A aplicação utiliza PHP para processar sessões, permissões e comunicação com a base de dados SQLite. O JavaScript é usado para interação com a interface, validações, eventos e tratamento de respostas JSON, especialmente no processo de login.

---

## Utilizadores de teste

Após executar `scripts/criar_bd.php`, são criados/atualizados os seguintes utilizadores de teste:

| Tipo          | Username    | Password   |
| ------------- | ----------- | ---------- |
| Administrador | admin       | admin123   |
| Restaurante   | restaurante | rest123    |
| Cliente       | cliente     | cliente123 |

---

## Notas de funcionamento

* A base de dados local é criada pelo ficheiro `scripts/criar_bd.php`;
* O ficheiro `data/foodtogo.db` não deve ser versionado no Git;
* Cada computador deve recriar a sua própria base de dados local;
* Restaurantes inativos não aparecem na lista pública de restaurantes;
* Produtos indisponíveis não devem ser usados para novas encomendas;
* O histórico de encomendas deve ser preservado sempre que possível.

## FoodToGo

## Tema

P17 - Encomenda de Alimentos

## Descrição da aplicação

A FoodToGo é uma aplicação Web de encomenda de alimentos online que tem como objetivo ligar clientes e restaurantes através de uma plataforma simples, organizada e intuitiva.

A aplicação permite que visitantes consultem os restaurantes disponíveis e visualizem os respetivos menus sem necessidade de criar conta. No entanto, para adicionar produtos ao carrinho, confirmar encomendas e acompanhar o estado do pedido, o utilizador deverá criar uma conta ou iniciar sessão como cliente.

O cliente autenticado poderá consultar restaurantes e menus, adicionar produtos ao carrinho, alterar quantidades, confirmar encomendas e acompanhar o estado da encomenda.

O restaurante poderá gerir os produtos do seu menu, atualizar preços, marcar produtos como disponíveis ou indisponíveis e gerir as encomendas recebidas.

O administrador será responsável pela gestão geral da plataforma, podendo gerir utilizadores, restaurantes, categorias de produtos e acompanhar o funcionamento global das encomendas.

## Atores

- Visitante
- Cliente
- Restaurante
- Administrador

## Funcionalidades principais

### Visitante

- Visualizar restaurantes disponíveis;
- Visualizar menus dos restaurantes;
- Criar conta;
- Iniciar sessão.

### Cliente

- Adicionar produtos ao carrinho;
- Alterar produtos ou quantidades no carrinho;
- Confirmar encomenda;
- Acompanhar o estado da encomenda;
- Consultar histórico de encomendas.

### Restaurante

- Registar restaurante;
- Iniciar sessão;
- Gerir menu;
- Adicionar, editar ou remover produtos;
- Atualizar preços;
- Atualizar disponibilidade dos produtos;
- Consultar encomendas recebidas;
- Atualizar estado das encomendas.

### Administrador

- Iniciar sessão no painel de administração;
- Gerir restaurantes;
- Gerir utilizadores;
- Consultar encomendas da plataforma;
- Gerir categorias de produtos.

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

**CLI01 – Adicionar produtos ao carrinho**  
Como Cliente, quero adicionar produtos ao carrinho para preparar a minha encomenda.  
Prioridade: Alta

**CLI02 – Alterar carrinho**  
Como Cliente, quero alterar quantidades ou remover produtos do carrinho antes de confirmar a encomenda.  
Prioridade: Média

**CLI03 – Confirmar encomenda**  
Como Cliente, quero confirmar a encomenda para que o restaurante a receba.  
Prioridade: Alta

**CLI04 – Acompanhar estado da encomenda**  
Como Cliente, quero acompanhar o estado da encomenda para saber se foi recebida, está em preparação ou foi concluída.  
Prioridade: Média

**CLI05 – Consultar histórico de encomendas**  
Como Cliente, quero consultar o histórico das minhas encomendas para rever pedidos anteriores.  
Prioridade: Baixa

---

### Restaurante

**RES01 – Registar restaurante**  
Como Restaurante, quero registar o restaurante e definir os seus dados principais para poder vender na plataforma.  
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
Como Restaurante, quero visualizar encomendas pendentes ou em preparação para as poder processar.  
Prioridade: Alta

**RES07 – Atualizar estado da encomenda**  
Como Restaurante, quero atualizar o estado da encomenda para informar o cliente sobre o progresso do pedido.  
Prioridade: Alta

**RES08 – Apagar conta do restaurante**  
Como Restaurante, quero apagar a conta caso deixe de utilizar a plataforma.  
Prioridade: Baixa

---

### Administrador

**ADM01 – Iniciar sessão**  
Como Administrador, quero iniciar sessão no painel de administração para gerir a plataforma.  
Prioridade: Alta

**ADM02 – Gerir restaurantes**  
Como Administrador, quero validar, editar ou remover restaurantes para controlar quem pode vender na plataforma.  
Prioridade: Alta

**ADM03 – Gerir utilizadores**  
Como Administrador, quero consultar e gerir contas de clientes e restaurantes para manter a plataforma organizada.  
Prioridade: Média

**ADM04 – Consultar encomendas da plataforma**  
Como Administrador, quero consultar todas as encomendas realizadas para acompanhar o funcionamento geral do sistema.  
Prioridade: Média

**ADM05 – Gerir categorias de produtos**  
Como Administrador, quero criar e editar categorias de produtos para organizar os menus dos restaurantes.  
Prioridade: Baixa
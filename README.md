# 🚗 **Tabela FIPE 1.1.0 Plugin para WordPress e WooCommerce**

Este é um **plugin exclusivo** que permite listar **marcas, modelos e anos de carros** diretamente no WordPress com WooCommerce! Com um simples **shortcode**, você pode exibir o valor FIPE de veículos cadastrados na loja.

---

## ✨ **Funcionalidades**
- Integração com **WooCommerce** (plugin de e-commerce mais popular para WordPress).
- Cadastramento simplificado de veículos diretamente na página de produtos.
- Exibição do valor FIPE usando o shortcode `[tabelafipe]`.
- Interface amigável e intuitiva, com uma aba exclusiva para gerenciar as informações.

---

## 🚀 **Instalação**

### **Pré-requisitos**
1. **WordPress** instalado e configurado.
2. Plugin **WooCommerce** ativo na sua loja virtual.

### **Passo a Passo**
1. Baixe o arquivo do plugin [tabela-fipe.zip](https://github.com/user-attachments/files/18173028/tabela-fipe.zip)
.
2. Acesse o painel do WordPress:
   - Vá para **Plugins > Adicionar Novo**.
   - Clique em **Enviar Plugin**.
3. Arraste o arquivo `.zip` do plugin e clique em **Instalar Agora**.
   - ![Instalação do plugin](https://github.com/user-attachments/assets/043ece00-9080-4d8c-8ffe-1315374fdf8d)
4. Após a instalação, clique em **Ativar**.

---

## ⚙️ **Configuração**

### 1. **Aba Tabela FIPE**
Após ativar o plugin, uma nova aba chamada **"Tabela FIPE"** aparecerá no painel administrativo.  
*(Inicialmente, ela estará vazia, pois nenhum dado foi cadastrado ainda).*

- ![Aba Tabela FIPE](https://github.com/user-attachments/assets/2b1f331a-450d-43a0-99da-acf57ade47da)

### 2. **Cadastro de Veículos**
Quando estiver cadastrando ou editando um produto no WooCommerce, você verá uma nova aba chamada **"Tabela FIPE"**.

1. Preencha as informações corretamente (marca, modelo e ano).
2. Salve o produto.
3. Uma mensagem de sucesso será exibida.

- ![Cadastro de veículo](https://github.com/user-attachments/assets/e811fa6c-ae6a-4522-8c17-dc1bf8030b8b)

---

## 🛠️ **Uso do Shortcode**

Para exibir os valores FIPE na página ou postagem, basta adicionar o shortcode:

```php
[tabelafipe]

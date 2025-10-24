# 🌐 Site Local - Guia de Instalação

## 📋 Pré-requisitos

Para executar o projeto localmente, é necessário ter o [XAMPP](https://www.apachefriends.org/) instalado na versão mais recente recomendada. O pacote deve conter o Apache, o PHPMyAdmin e o PHP corretamente configurados.

---

## 🚀 Configuração do Ambiente

### 1. Configuração do Servidor Web

Abra o XAMPP Control Panel e inicie os serviços **Apache** e **MySQL**. Após ativá-los, o ambiente local estará pronto para receber os arquivos do projeto.

### 2. Configuração do Banco de Dados

A configuração do banco de dados pode ser feita de duas formas:

🅰️ **Opção A: Importar Banco Existente (Recomendado)**  
Acesse o endereço [http://localhost/phpmyadmin](http://localhost/phpmyadmin), clique na aba **Importar**, selecione o arquivo de banco de dados que está localizado na pasta `/db` do projeto e clique em **Executar** para concluir o processo.

🅱️ **Opção B: Executar Script SQL**  
Caso prefira criar o banco de dados manualmente, acesse o PHPMyAdmin, crie um novo banco de dados e em seguida execute o script SQL que está na pasta `/db` do projeto.

### 3. Instalação dos Arquivos

Copie todos os arquivos do projeto para a pasta de execução do seu sistema operacional:  
No **Windows**, utilize o caminho `C:\xampp\htdocs\`;  
no **macOS**, utilize `/Applications/XAMPP/htdocs/`;  
e no **Linux**, utilize `/opt/lampp/htdocs/`.  
Após copiar os arquivos, o projeto estará acessível localmente.

### 4. Acesso ao Site

Após a instalação, abra o navegador e acesse o endereço [http://localhost/](http://localhost/) para visualizar o site. Caso o projeto esteja dentro de uma subpasta, use o caminho [http://localhost/nome-da-pasta/](http://localhost/nome-da-pasta/).

---

## 🔐 Contas de Acesso

O sistema possui dois tipos de contas padrão.  
A conta comum é destinada a registros de **Colaborador**.  
Já a conta administrativa possui as seguintes credenciais:  
E-mail: `samuel@gmail.com`  
Senha: `12345678`  
⚠️ **Aviso de Segurança:** É essencial alterar essas credenciais em ambiente de produção para garantir a proteção do sistema.

---

## 🐛 Solução de Problemas

Em caso de erros durante a execução do projeto, siga as orientações abaixo:  
- **Erro de conexão com o banco de dados:** verifique se o serviço MySQL está ativo no XAMPP.  
- **Página não encontrada:** confirme se os arquivos do projeto estão corretamente posicionados na pasta `htdocs`.  
- **Permissões negadas:** confira as permissões do Apache e ajuste caso necessário.

---

## 📁 Estrutura do Projeto

A estrutura de diretórios do projeto é organizada da seguinte forma:  
projeto/  
├── db/  
│ ├── database.sql  
│ └── script.sql  
├── assets/  
├── includes/  
└── ...

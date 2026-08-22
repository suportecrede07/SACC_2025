# SACC - Sistema de Avaliação do Ceará Científico

Este repositório contém a implementação de um sistema web para gestão e avaliação de trabalhos científicos em um evento escolar. O projeto foi pensado para apoiar a administração de inscrições, atribuição de jurados, registro de notas e geração de relatórios consolidados por escola, categoria, área e jurado.

## Escopo do projeto

O sistema cobre o ciclo completo de uma avaliação de trabalhos acadêmicos e científicos, incluindo:

- Cadastro e gestão de escolas;
- Cadastro e gestão de jurados;
- Cadastro de trabalhos por escola, categoria e área;
- Associação de jurados aos trabalhos conforme categoria e área;
- Login de administrador e de jurado;
- Avaliação dos trabalhos com critérios e comentários;
- Geração de relatórios em PDF e Excel;
- Visualização de ranking e comparativos por escola e jurado.

Em termos práticos, o sistema foi construído para atender a uma estrutura de competição ou evento de ciência escolar, no qual:

1. a administração registra as instituições participantes;
2. cadastra os jurados e seus vínculos com categorias e áreas;
3. registra os trabalhos submetidos;
4. associa jurados aos trabalhos relevantes;
5. os avaliadores atribuem notas em critérios específicos;
6. os resultados são consolidados em relatórios.

## Funcionalidades principais

### Painel administrativo

O administrador pode acessar o dashboard para:

- cadastrar e consultar instituições/escolas;
- cadastrar jurados;
- cadastrar trabalhos;
- associar jurados a trabalhos;
- visualizar estatísticas resumidas;
- acessar relatórios.

### Painel do jurado

O jurado acessa uma área específica para:

- visualizar os trabalhos atribuídos;
- abrir a ficha de avaliação de cada trabalho;
- preencher notas em diversos critérios;
- registrar comentários;
- enviar a avaliação final.

### Processo de avaliação

Cada trabalho pode receber avaliação de um jurado com critérios como:

- criatividade e inovação;
- relevância da pesquisa;
- conhecimento científico e contextualização;
- impacto para a sociedade;
- metodologia científica;
- clareza de linguagem;
- banner;
- caderno de campo;
- processo participativo e solidário.

As notas são validadas no intervalo de 0 a 10, com até duas casas decimais.

### Relatórios

O projeto inclui geração de relatórios para:

- escola;
- jurado;
- trabalho individual;
- ranking geral;
- ambos os jurados;
- exportação para PDF e Excel.

## Stack tecnológica

- PHP 7+
- MySQL
- HTML + CSS + JavaScript
- Bootstrap
- Dompdf para geração de PDF
- PhpSpreadsheet para exportação em Excel

## Estrutura do repositório

- [index.html](index.html): página inicial com escolha de login;
- [html/](html): telas do sistema (administração, jurados, relatórios e dashboards);
- [php/](php): lógica de autenticação, cadastro, atualização e persistência;
- [sql/SACC.sql](sql/SACC.sql): script de criação do banco e dados iniciais;
- [pdf/](pdf): geração de relatórios em PDF;
- [assets/](assets): imagens, ícones e estilos;
- [boostrap/](boostrap): arquivos do Bootstrap e jQuery;
- [dompdf/](dompdf): biblioteca para geração de documentos PDF;
- [vendor/](vendor): dependências do Composer;
- [composer.json](composer.json): dependências do projeto.

## Requisitos

Antes de executar o projeto, certifique-se de que o ambiente tenha:

- PHP instalado;
- MySQL ou MariaDB instalado;
- Apache, Nginx ou XAMPP/WAMP/LAMP funcionando;
- Composer instalado para manutenção de dependências.

## Configuração do banco de dados

1. Crie o banco de dados MySQL chamado SACC;
2. Importe o arquivo [sql/SACC.sql](sql/SACC.sql);
3. Ajuste as credenciais de conexão em [php/Connect.php](php/Connect.php).

O arquivo de conexão atual usa a seguinte configuração padrão:

```php
$pdo = new PDO("mysql:host=localhost;dbname=SACC","root","cr701201");
```

Se o seu ambiente usa outra senha ou usuário, altere esse trecho antes de iniciar o sistema.

## Credenciais padrão

O script SQL cria um usuário administrativo padrão.

Esses dados são importantes para o primeiro acesso ao painel administrativo.

## Como executar

### Opção 1: XAMPP/WAMP/LAMP

1. Coloque a pasta do projeto na pasta pública do servidor, como htdocs;
2. Inicie o Apache e o MySQL;
3. Acesse a URL do projeto no navegador;
4. Selecione o tipo de login: Admin ou Jurado.

### Opção 2: servidor PHP local

Na raiz do projeto, você pode usar um ambiente simples com PHP embutido, caso esteja configurado:

```bash
php -S localhost:8000
```

Depois acesse:

```text
http://localhost:8000/
```

## Guia de instalação para uso em produção

A seguir está um guia mais profissional para implantar o projeto em um ambiente de produção, com foco em estabilidade, usabilidade e segurança básica.

### 1. Pré-requisitos

- Servidor web com PHP 8.x ou compatível;
- Apache ou Nginx configurado para hospedar o projeto;
- MySQL 8 ou MariaDB compatível;
- Composer instalado;
- acesso administrativo ao servidor para configurar permissões e virtual hosts;
- domínio ou subdomínio próprio, se a aplicação for pública.

### 2. Clonar ou publicar o projeto

No servidor, clone ou transfira a aplicação para o diretório público do host, por exemplo:

```bash
cd /var/www
git clone <url-do-repositorio> sacc
```

Se a aplicação for hospedada em outra estrutura, ajuste o caminho de publicação conforme o servidor utilizado.

### 3. Instalar dependências

O projeto usa Composer para dependências externas. Execute:

```bash
cd /var/www/sacc
composer install --no-dev
```

Se a pasta `vendor/` já estiver presente, verifique se ela está completa e acessível pelo servidor.

### 4. Preparar o banco de dados

1. Crie o banco MySQL chamado `SACC`;
2. Importe o script SQL:

```bash
mysql -u <usuario> -p < sql/SACC.sql
```

Ou via ferramenta visual como phpMyAdmin.

### 5. Ajustar credenciais de conexão

Edite o arquivo [php/Connect.php](php/Connect.php) para configurar o host, usuário, senha e nome do banco de acordo com o ambiente de produção.

Exemplo:

```php
$pdo = new PDO(
    "mysql:host=localhost;dbname=SACC;charset=utf8mb4",
    "seu_usuario",
    "sua_senha"
);
```

Também é recomendado ativar tratamento de exceções e desabilitar a exibição de erros em produção:

```php
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### 6. Configurar permissões do servidor

Garanta que o diretório da aplicação tenha permissões adequadas para leitura e execução dos arquivos PHP, sem expor pastas sensíveis publicamente.

Exemplo em Linux:

```bash
sudo chown -R www-data:www-data /var/www/sacc
sudo find /var/www/sacc -type f -exec chmod 644 {} \;
sudo find /var/www/sacc -type d -exec chmod 755 {} \;
```

### 7. Configurar o virtual host do Apache

Um exemplo de configuração do Apache para produção:

```apache
<VirtualHost *:80>
    ServerName sacc.exemplo.com.br
    DocumentRoot /var/www/sacc

    <Directory /var/www/sacc>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sacc_error.log
    CustomLog ${APACHE_LOG_DIR}/sacc_access.log combined
</VirtualHost>
```

Depois habilite o site:

```bash
sudo a2ensite sacc.conf
sudo systemctl reload apache2
```

### 8. Ajustes de segurança recomendados

Antes de usar em produção, recomenda-se:

- desabilitar `display_errors` no PHP;
- evitar credenciais fixas no código;
- trocar a senha do usuário administrativo padrão após o primeiro acesso;
- usar HTTPS com certificado válido;
- restringir acesso a arquivos de configuração e scripts sensíveis;
- validar entradas e sanitizar dados antes de persistir no banco;
- manter o sistema atualizado e revisar logs de acesso.

### 9. Configuração de ambiente de produção

Para um ambiente profissional, é altamente recomendável:

- separar variáveis de ambiente em arquivo de configuração;
- manter a conexão ao banco em um único ponto centralizado;
- usar criptografia para senhas em vez de armazenamento em texto puro;
- criar backups automáticos do banco;
- implementar monitoramento de erro e logs de auditoria.

### 10. Verificação final

Após a configuração, teste os fluxos principais:

1. acesso ao login administrativo;
2. acesso do jurado;
3. cadastro de escola;
4. cadastro de jurado;
5. cadastro de trabalho;
6. associação de jurado ao trabalho;
7. avaliação;
8. geração de relatório.

Se todos os passos funcionarem corretamente, a aplicação estará pronta para uso em ambiente de produção ou de homologação.

## Fluxo de uso do sistema

### Administração

- login no painel administrativo;
- cadastro de escolas, jurados e trabalhos;
- associação de jurados conforme categoria/área;
- consulta de relatórios e resultados.

### Jurado

- login como jurado;
- visualização da lista de trabalhos atribuídos;
- preenchimento das notas e comentários;
- envio da avaliação.

## Observações importantes

- A aplicação usa PHP puro e não segue uma arquitetura moderna MVC;
- Parte da lógica e das views estão misturadas em arquivos PHP e HTML;
- Há registros de autenticação por sessão com credenciais armazenadas em banco;
- O projeto foi implementado como aplicação interna/educacional e não foi totalmente revisado para produção; recomenda-se reforço em segurança antes de uso em ambiente público;
- Há dependências em bibliotecas locais da pasta [vendor/](vendor), além de pastas da biblioteca externa [dompdf/](dompdf).

## Possíveis melhorias futuras

- reorganizar a estrutura em MVC ou estrutura modular;
- implementar autenticação com senha hash;
- validar permissões e autorização por perfil de usuário;
- padronizar nomes de tabelas e campos em português/inglês;
- criar testes automatizados;
- centralizar a configuração em um arquivo de ambiente;
- melhorar a consistência da camada de relatórios e exportação.

## Conclusão

O projeto é uma solução funcional para gestão da avaliação de trabalhos científicos em nível escolar ou institucional. Ele reúne operações administrativas e de avaliação em um único sistema, com foco em organização do processo, cadastro de participantes e geração de relatórios técnicos.

Este README tem como objetivo apresentar o escopo real do software e facilitar a configuração e manutenção do ambiente de desenvolvimento.

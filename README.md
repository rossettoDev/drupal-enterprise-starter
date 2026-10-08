# drupal-enterprise-starter

Base Drupal 11 para desenvolvimento local com **Lando** no **WSL2**, usando o **Docker Desktop** como engine.

O PHP desta distro Ubuntu é 8.1. O Drupal 11 exige 8.3, então Composer, Drush e o servidor web rodam dentro do Lando. Rode os comandos no terminal do WSL, com o projeto em `/home` (filesystem Linux). Não use a cópia em `/mnt/c`.

## Pré-requisitos

- WSL2 (Ubuntu)
- Docker Desktop com a integração WSL habilitada para esta distro
- Lando 3 instalado dentro do WSL (`~/.lando/bin/lando` no `PATH`)

O cliente Docker do WSL precisa enxergar o daemon do Docker Desktop (`/var/run/docker.sock`).

## Subir o ambiente

```bash
lando start
```

Na primeira execução o Lando baixa as imagens (PHP 8.3, Apache 2.4, MariaDB 10.11) e roda `composer install`.

- Site: https://drupal-enterprise-starter.lndo.site
- Banco: host `database`, banco/usuário/senha `drupal11`

O domínio `*.lndo.site` aponta para `127.0.0.1`. Com o Docker Desktop, as portas 80 e 443 publicadas no WSL também respondem no localhost do Windows.

## Instalar o Drupal

```bash
lando drush site:install standard \
  --db-url=mysql://drupal11:drupal11@database:3306/drupal11 \
  --account-name=admin \
  --account-pass=admin \
  --site-name="Drupal Enterprise Starter" \
  -y
```

O `--db-url` é necessário na primeira instalação: o `settings.php` ainda não existe e o Drush, fora de um prompt interativo, não descobre o MariaDB do Lando sozinho.

## Comandos

| Comando | Uso |
| --- | --- |
| `lando composer <cmd>` | Composer no PHP 8.3 |
| `lando drush <cmd>` | Drush do projeto |
| `lando php -v` | PHP do container |
| `lando mysql` | Cliente MariaDB |
| `lando stop` | Para os containers |
| `lando destroy -y` | Remove containers e o volume do banco |

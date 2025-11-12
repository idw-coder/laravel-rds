## Windows + Docker + Laravelは遅い問題

Windowsのファイル（C:ドライブ）をDockerコンテナ（Linux）がマウントすると、
ファイルアクセスのたびに変換が発生
Laravelはvendor/に数千〜数万のファイルがあり、それを頻繁に読む
この変換処理で極端に遅くなる

### 参考記事
- [「WindowsでDockerを動かしたら遅かった😥」を解決する方法をまとめました。](https://zenn.dev/conbrio/articles/fcf937c4049132)
- [Windows + WSL2 + docker + laravel を 10 倍速くする方法](https://www.aska-ltd.jp/jp/blog/197)

##  開発仕様（AI連携用プロンプト）

・Laravel API バックエンドプロジェクト
・レンタルサーバーと AWS の両方にデプロイ可能な構成
・ローカル環境: Windows 11 + WSL2 (Ubuntu) + Docker Desktop + Laravel Sail
・プロジェクト配置: `/home/wida/dev/laravel-rds` (WSL2 Ubuntu内)
・配置理由: Windows ファイルシステムとの変換オーバーヘッドを回避し高速化
・データベース接続:
  - ローカル開発: Docker MySQL
  - レンタルサーバー: レンタルサーバーの MySQL
  - AWS: Amazon RDS (MySQL)
・デプロイ先:
  - レンタルサーバー: FTP/SSH でデプロイ
  - AWS: EC2 または Elastic Beanstalk + RDS
・技術スタック: PHP 8.2+、Laravel 12.x、MySQL 8.0
・リポジトリ: `git@github.com:idw-coder/laravel-rds.git`
・ブランチ戦略: main ブランチ運用

## 構成

```
laravel-rds/
├── docs/
│   ├── setup.md           # 環境構築手順
│   ├── deployment.md      # デプロイ手順（レンタル/AWS）
│   ├── database.md        # DB接続設定
│   ├── api.md             # API仕様
│   └── troubleshooting.md # よくある問題
├── README.md              # プロジェクト概要
```

## 手順

先にディレクトリ作成してGit初期化したので別のディレクトリにインストールして、
移動しました

```bash
wida@LAPTOP-2C4PL9J8:~/dev$ curl -s "https://laravel.build/laravel-rds-temp" | bash
latest: Pulling from laravelsail/php84-composer
Digest: sha256:a2716e93e577c80bca7551126056446c1e06cb141af652ee6932537158108400
Status: Image is up to date for laravelsail/php84-composer:latest

In NewCommand.php line 789:

  Application already exists!


new [--dev] [--git] [--branch BRANCH] [--github [GITHUB]] [--organization ORGANIZATION] [--database DATABASE] [--stack [STACK]] [--breeze] [--jet] [--dark] [--typescript] [--eslint] [--ssr] [--api] [--teams] [--verification] [--pest] [--phpunit] [--prompt-breeze] [--prompt-jetstream] [-f|--force] [--] <name>

WARN[0000] The "MYSQL_EXTRA_OPTIONS" variable is not set. Defaulting to a blank string.
WARN[0000] The "MYSQL_EXTRA_OPTIONS" variable is not set. Defaulting to a blank string.
[+] Pulling 5/5
 ✔ redis Pulled                                                                                                    3.0s
 ✔ selenium Pulled                                                                                                 3.0s
 ✔ meilisearch Pulled                                                                                              2.9s
 ✔ mailpit Pulled                                                                                                  3.0s
 ✔ mysql Pulled                                                                                                    3.0s
WARN[0000] The "MYSQL_EXTRA_OPTIONS" variable is not set. Defaulting to a blank string.
WARN[0000] The "MYSQL_EXTRA_OPTIONS" variable is not set. Defaulting to a blank string.
[+] Building 839.7s (21/21) FINISHED
 => [internal] load local bake definitions                                                                                    0.0s
 => => reading from stdin 493B                                                                                                0.0s
 => [internal] load build definition from Dockerfile                                                                          0.0s
 => => transferring dockerfile: 3.71kB                                                                                        0.0s
 => [internal] load metadata for docker.io/library/ubuntu:24.04                                                               2.1s
 => [internal] load .dockerignore                                                                                             0.1s
 => => transferring context: 2B                                                                                               0.0s
 => [ 1/14] FROM docker.io/library/ubuntu:24.04@sha256:66460d557b25769b102175144d538d88219c077c678a49af4afca6fbfc1b5252       0.0s
 => [internal] load build context                                                                                             0.1s
 => => transferring context: 99B                                                                                              0.0s
 => CACHED [ 2/14] WORKDIR /var/www/html                                                                                      0.0s
 => CACHED [ 3/14] RUN ln -snf /usr/share/zoneinfo/UTC /etc/localtime && echo UTC > /etc/timezone                             0.0s
 => CACHED [ 4/14] RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom &&     echo "Acquire::http::No  0.0s
 => [ 5/14] RUN apt-get update && apt-get upgrade -y     && mkdir -p /etc/apt/keyrings     && apt-get install -y gnupg gos  804.7s
 => [ 6/14] RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.4                                                             0.5s
 => [ 7/14] RUN userdel -r ubuntu                                                                                             1.0s
 => [ 8/14] RUN groupadd --force -g 1001 sail                                                                                 0.6s
 => [ 9/14] RUN useradd -ms /bin/bash --no-user-group -g 1001 -u 1337 sail                                                    0.7s
 => [10/14] RUN git config --global --add safe.directory /var/www/html                                                        0.5s
 => [11/14] COPY start-container /usr/local/bin/start-container                                                               0.2s
 => [12/14] COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf                                                     0.1s
 => [13/14] COPY php.ini /etc/php/8.4/cli/conf.d/99-sail.ini                                                                  0.2s
 => [14/14] RUN chmod +x /usr/local/bin/start-container                                                                       0.0s
 => exporting to image                                                                                                       27.3s
 => => exporting layers                                                                                                      27.2s
 => => writing image sha256:91e56947d48146f32847ed210f29b66597942fdea00a94185e1393f7b02a0a17                                  0.0s
 => => naming to sail-8.4/app                                                                                                 0.0s
 => resolving provenance for metadata file                                                                                    0.0s
[+] Building 1/1
 ✔ laravel.test  Built                                                                                                        0.0s

Please provide your password so we can make some final adjustments to your application's permissions.

[sudo] password for wida:

Thank you! We hope you build something incredible. Dive in with: cd laravel-rds-temp && ./vendor/bin/sail up
```

```bash
git push origin main
```

```bash
wida@LAPTOP-2C4PL9J8:~/dev/laravel-rds$ ./vendor/bin/sail up -d
WARN[0000] The "MYSQL_EXTRA_OPTIONS" variable is not set. Defaulting to a blank string.
WARN[0000] The "MYSQL_EXTRA_OPTIONS" variable is not set. Defaulting to a blank string.
[+] Running 6/6
 ✔ Container laravel-rds-meilisearch-1   Running                                                                              0.0s
 ✔ Container laravel-rds-mailpit-1       Running                                                                              0.0s
 ✔ Container laravel-rds-redis-1         Running                                                                              0.0s
 ✔ Container laravel-rds-selenium-1      Running                                                                              0.0s
 ✔ Container laravel-rds-mysql-1         Started                                                                              0.4s
 ✔ Container laravel-rds-laravel.test-1  Started                                                                              0.5s
wida@LAPTOP-2C4PL9J8:~/dev/laravel-rds$ docker ps
CONTAINER ID   IMAGE                          COMMAND                  CREATED         STATUS                            PORTS                                                                                                NAMES
a9ae08ca2027   sail-8.4/app                   "start-container"        5 minutes ago   Up 8 seconds                      0.0.0.0:80->80/tcp, [::]:80->80/tcp, 0.0.0.0:5173->5173/tcp, [::]:5173->5173/tcp                     laravel-rds-laravel.test-1
a31d8367e0c0   mysql/mysql-server:8.0         "/entrypoint.sh mysq…"   5 minutes ago   Up 8 seconds (health: starting)   0.0.0.0:3306->3306/tcp, [::]:3306->3306/tcp, 33060-33061/tcp                                         laravel-rds-mysql-1
001cf9f673d7   selenium/standalone-chromium   "/opt/bin/entry_poin…"   5 minutes ago   Up 5 minutes                      4444/tcp, 5900/tcp, 9000/tcp                                                                         laravel-rds-selenium-1
d8c98e675d07   redis:alpine                   "docker-entrypoint.s…"   5 minutes ago   Up 5 minutes (healthy)            0.0.0.0:6379->6379/tcp, [::]:6379->6379/tcp                                                          laravel-rds-redis-1
29bddfe790ae   axllent/mailpit:latest         "/mailpit"               5 minutes ago   Up 5 minutes (healthy)            0.0.0.0:1025->1025/tcp, [::]:1025->1025/tcp, 0.0.0.0:8025->8025/tcp, [::]:8025->8025/tcp, 1110/tcp   laravel-rds-mailpit-1
45ed6632190f   getmeili/meilisearch:latest    "tini -- /bin/sh -c …"   5 minutes ago   Up 5 minutes (healthy)            0.0.0.0:7700->7700/tcp, [::]:7700->7700/tcp
```

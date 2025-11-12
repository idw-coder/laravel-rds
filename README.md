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

```bash

```
## Windows + Docker + Laravelは遅い問題

Windowsのファイル（C:ドライブ）をDockerコンテナ（Linux）がマウントすると、
ファイルアクセスのたびに変換が発生
Laravelはvendor/に数千〜数万のファイルがあり、それを頻繁に読む
この変換処理で極端に遅くなる

### 参考記事
- [「WindowsでDockerを動かしたら遅かった😥」を解決する方法をまとめました。](https://zenn.dev/conbrio/articles/fcf937c4049132)
- [Windows + WSL2 + docker + laravel を 10 倍速くする方法](https://www.aska-ltd.jp/jp/blog/197)
- 
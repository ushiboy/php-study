# PHP Study

勉強会用資料

## 概要

PHP7系を利用して書き方、動かし方をざっと触れてみましょう的な話。

## おしながき

* 文法的な話
  * php.netの言語リファレンスからざっと見
  * 他の言語から来た人がハマりやすいポイント（できたら）
* 実行環境的な話
  * nginx FastCGIなあたりの動きとか
  * OPcache（できたら）
* その他
  * PSR的な話
  * Composerな話
  * 開発環境的な話

## 環境セットアップ

Vagrantで環境を作ります。

```
$ vagrant up
```

[http://localhost:9090/info.php](http://localhost:9090/info.php)にアクセスしてphpinfoのページが表示されればOK。

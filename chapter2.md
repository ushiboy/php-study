# PHPの処理の流れとサーバ環境

## PHPプログラムが実行されるまでの流れ

### コンパイルと実行

* PHPはコンパイルを行うことなくプログラムを実行できる
* 実際にはPHPプログラムが実行されるたびに内部的にソースコードのコンパイルを行う
  * PHPプログラムが実行されると、内部でオペコードと呼ばれる中間コードにコンパイルされる
  * オペコードを実行マシンが実行する
* オペコードの生成過程
  * レキサーがPHPプログラムを解析し、構文をトークンに分解
  * パーサーが構文トークンをパースし、オペコードに変換

```
| php プログラム | --(レキサー)--> | トークン | --(パーサー)--> | オペコード | --(実行マシン)--> | 実行結果 |
```

### オペコードキャッシング

* PHPプログラムに変更がない場合、実行ごとにオペコードへ変換することは無駄になる
* オペコードをキャッシュすることで高速化する手法（APCなど）がある
* APC
  * Alternative PHP Cache
  * PHPソースコードの中間コードキャッシュなどを行う拡張機能
  * 主な機能
    * PHPスクリプトの中間コード（オペコード）をキャッシュ
    * メモリ上のkey-valueストア
  * 今はメンテ止まってるのでOPcacheなどで代替
  * [参考:PHPマニュアル/関数リファレンス/PHPの振る舞いの変更/Alternative PHP Cache](http://php.net/manual/ja/book.apc.php)

## サーバ環境

* モジュール
* CGI
* PHP FPM

### モジュール

* Webサーバと同じプロセスでPHPを実行させる
* Webサーバの機能の一部としてPHPが動く
* Apacheの`mod_php`とか

### CGI

* Common Gateway Interface
* Webサーバがリクエストに応じて様々なプログラムを実行する仕組み
* 実行権限のあるPHPプログラムをWebサーバが実行する形式で動作
* 他の言語の場合と同様
* Webサーバと実行されるプログラムは別のプロセスとして動く
* リクエストのたびにプログラムは破棄される
* モジュールとCGIの比較
  * モジュール形式の方が一般的に高速

### PHP FPM(FastCGI Process Manager)

* PHPのFastCGI実装の一つ
  * もともとは公式ではなかった
    * [PHP FPM公式](https://php-fpm.org/)
  * 5.4系からオフィシャルになった模様
* 高負荷サイトで有用な機能を持つ
  * 機能
    * 緩やかな停止(graceful shutdown)/起動 機能を含む高度なプロセス管理
    * 異なるuid/gid/chroot/environmentでのワーカーの開始
    * 異なるポートでのリッスン
    * 異なるphp.iniの仕様(`safe_mode`の代替)
    * 標準出力及び標準エラー出力へのログ出力
    * opcodeキャッシュが壊れた場合の緊急再起動
    * 高度なアップロードのサポート
    * 実行時間が非常に長いスクリプトの記録 "showlog"
    * `fastcgi_finish_request()`何らか時間のかかる処理を継続させながらリクエストを終了させて全てのデータを出力させるための関数
    * 動的/静的な子プロセスの起動
    * 基本的なSAPIの動作状況(Apacheの`mod_status`と同等)
    * php.iniベースの設定ファイル
* インストール
  * ソースからコンパイルする場合
    * configureで--enable-fpmを追加
  * パッケージの場合は該当パッケージを追加
    * APTなら`apt-get install php7.1-fpm`とか
* 設定
  * php-fpm.confとプール設定ファイルを使う


## FastCGI

* プロセスをメモリ上に永続的に起動させたまままにする
* 起動と終了にかかる時間を削減
* プログラム動作速度の向上およびサーバ負荷の低下
* 最初にプロセスが実行された段階で、そのプロセスはメモリ上に存続し続け、次の要求に対してはそのプロセスで実行する

### PythonでのFastCGIサンプル

シンプルなカウンタ

```python
#!/usr/bin/env python3
# -*- coding: UTF-8 -*-

# http://docs.python.jp/2/howto/webservers.html#fastcgi-and-scgi

import sys, os
from flup.server.fcgi import WSGIServer

count = 0

def app(environ, start_response):
    global count
    start_response('200 OK', [('Content-Type', 'text/plain')])
    count = count + 1
    return ['Hello World!\n', 'Count: %s\n' % count]

WSGIServer(app, bindAddress='/tmp/fcgi.sock').run()
```

起動

```
$ vagrant ssh
$ cd /var/sample/python/
$ ./launch.sh

$ curl localhost:9090/python_sample/
```

アクセスするたびにカウントが増える。


### PHPのstatic変数を使ってやってみる

static変数については[PHPマニュアル/言語リファレンス/変数/静的変数の使用](http://php.net/manual/ja/language.variables.scope.php#language.variables.scope.static)を参照。

最初にREPLで試したいので、こんな感じのライブラリにする。

/vagrant/html/counter.php
```php
<?php

function counter() {
  static $a = 0;
  $a++;
  return $a;
}
```

REPLで動作確認してみる。

```
$ php -a
Interactive shell
php > require_once('./counter.php');
php > echo counter();
1
php > echo counter();
2
php > echo counter();
3
```

WEBサーバのドキュメントルートで参照して使う。

/vagrant/html/count.php
```php
<?php
require_once('./counter.php');

echo "Count:" .counter();
```

ブラウザアクセスして確認する。

```
$ curl localhost:9090/count.php
```

### (おまけ) php-fpmの動きをデバッガで雑に追ってみる

* `php-7.1.10/sapi/fpm/fpm/fpm_main.c`
  * `main()`関数
    * 1966行目: `php_execute_script`
* `php-7.1.10/main/main.c`
  * `php_execute_script()`関数
    * 2552行目: `zend_execute_scripts`
* `php-work/php-7.1.10/Zend/zend.c`
  * `zend_execute_scripts()`関数
    * 1474行目: `zend_compile_file`
      * スクリプトのバイトコードへのコンパイルを行う
    * 1480行目: `zend_execute`
      * バイトコードの実行を行う


[参考:PHPによるhello world入門](http://tech.respect-pal.jp/php-helloworld/1434656422/)

## OPCache

* コンパイル済みのバイトコードを共有メモリに保存
* リクエストのたびにスクリプトを読み込みパースする手間を省く
* 5.5系から標準添付
* APCの代替
* [参考:PHP マニュアル/関数リファレンス/PHPの振る舞いの変更/OPCache](http://php.net/manual/ja/book.opcache.php)
* 参考
  * [Zend OPcacheの速さの秘密を探る](https://www.slideshare.net/hnw/zend-opcache)
  * [realpathキャッシュとOPcacheの面倒すぎる関係](https://www.slideshare.net/hnw/realpath-opcache)

### OPCacheを使っての注意点的なことを体験

* /vagrant/myapp/current/mylib.php を参照する/vagrant/html/myapp.phpというファイルを用意
* ただし/vagrant/myapp/currentは/vagrant/myapp/releases/20170901000000のシンボリックリンクとする

/vagrant/myapp/releases/20170901000000/mylib.php
```php
<?php
function greet() {
    return "hello!";
}
```

/vagrant/myapp/releases/20171001000000/mylib.php
```php
<?php
function greet() {
    return "こんにちわ!";
}
```

/vagrant/html/myapp.php
```php
<?php
require_once('../myapp/current/mylib.php');

echo greet()."\n";
```

動作確認

```
$ curl localhost:9090/myapp.php

$ vagrant ssh
$ cd /vagrant/myapp
$ rm current
$ ln -s myapp/releases/20170901000000 current
$ exit

$ curl localhost:9090/myapp.php

$ vagrant ssh
$ sudo service php7.1-fpm restart
$ exit

$ curl localhost:9090/myapp.php
```


# Composer

* PHPのパッケージ（ライブラリ・ツール）マネージャー
  * [公式](https://getcomposer.org/)
  * Node.jsで言うところのnpmみたいなもの
* デフォルトリポジトリは[Packagist](https://packagist.org/)
* 機能
  * 依存パッケージの管理
  * PHPバージョンや必須PHP拡張の定義
  * コマンドラインツールのインストール
  * クラスのオートローディング

## Composer体験

### プロジェクトの初期化

Composer用にプロジェクトを初期化(`composer init`)する。

```
$ vagrant ssh
$ cd /vagrant
$ composer init

Welcome to the Composer config generator

This command will guide you through creating your composer.json config.

Package name (<vendor>/<name>) [root/vagrant]:
Description []:
Author [, n to skip]: n
Minimum Stability []:
Package Type []:
License []:

Define your dependencies.

Would you like to define your dependencies (require) interactively [yes]? no
Would you like to define your dev dependencies (require-dev) interactively [yes]? no

{
    "name": "root/vagrant",
    "require": {}
}

Do you confirm generation [yes]? yes
Would you like the vendor directory added to your .gitignore [yes]? no
```

### 依存パッケージの追加

軽量WAFの[Slim](https://www.slimframework.com/)を追加(`composer requrie`)する。

```
$ composer require slim/slim
```

vendorディレクトリ配下に配置されたことを確認する。


### 追加した依存パッケージを使ってみる

/vagrant/html/index.php を作成する。

```php
<?php
require '../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;
$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");

    return $response;
});
$app->run();
```

ポイントは先頭で`require '../vendor/autoload.php';`してcomposerが生成したオートロードのファイルを読み込むこと。

オートロードが解決してくれるので、個々にrequireする必要がない。


### 動作確認

諸事情により、PHPのビルトインサーバ使う。

ビルトインサーバについては後で説明。

```
$ vagrant ssh
$ cd /vagrant
$ php -S 0.0.0.0:1234 -t html
$ curl localhost:1234/hello/hoge
```

### アプリケーションなどで独自ネームスペースのプログラムを使う方法

srcディレクトリ配下をMyAppネームスペースで管理することにして、src/MyClass.phpファイルを作成する。

/vagrant/src/MyClass.php
```php
<?php
namespace MyApp;

class MyClass
{
    function hello($name) {
        return "Hello, $name";
    }
}
```

html/index.phpファイルで`\MyApp\MyClass`を利用するように手を加える。

/vagrant/html/index.php
```php
<?php
require '../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app = new \Slim\App;
$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write((new \MyApp\MyClass())->hello($name));  // <- 修正

    return $response;
});
$app->run();
```

このままではオートロードがMyAppを解決できないので、composer.jsonに設定を行う。

MyAppネームスペースはsrcディレクトリで扱うことを定義する。

PSRについては後で説明。

composer.json
```
{
    "name": "root/vagrant",
    "require": {
        "slim/slim": "^3.8"
    },
    "autoload": {
        "psr-4": {
            "MyApp\\": "src/"
        }
    }
}
```

設定を反映(`composer dump-autoload`)する。

```
$ composer dump-autoload
```

### 余談

フロントコントローラパターン（URLのパスをすべて1つのindex.phpで受ける）にはWebサーバのリライトのチカラを借りる。

[参考](https://www.slimframework.com/docs/start/web-servers.html)


# PSR

* 有志による具体的な設計仕様の提案
* [公式サイト](http://www.php-fig.org/)
* どんなのがあるか
  * 受理されたステータスのもの
    * PSR-1: Basic Coding Standard
    * PSR-2: Coding Style Guide
    * PSR-3: Logger Interface
    * PSR-4: Autoloading Standard
    * PSR-6: Caching Interface
    * PSR-7: HTTP Message Interface
    * PSR-11: Container Interface
    * PSR-13: Hypermedia Links
    * PSR-16: Simple Cache
  * [参考](https://qiita.com/rana_kualu/items/f41d8f657df7709bda0f)


## PSR-2 Coding Style Guide

* コーディングスタイルの規約
  * [公式](http://www.php-fig.org/psr/psr-2/)
  * [日本語訳](http://www.infiniteloop.co.jp/docs/psr/psr-2-coding-style-guide.html)
* 新たに置き換えを目指したPSR-12というのが検討中みたい

### チェックツールで試してみる

`php_codesniffer`を入れる。

```
$ composer require --dev squizlabs/php_codesniffer
```

compsoerのscriptsを使って実行しやすくしておく。

composer.json
```
{
    "name": "root/vagrant",
    "scripts": {
        "phpcs": "phpcs --standard=PSR2 src"
    },
    "require": {
        "slim/slim": "^3.8"
    },
    "autoload": {
        "psr-4": {
            "MyApp\\": "src/"
        }
    },
    "require-dev": {
        "squizlabs/php_codesniffer": "^3.1"
    }
}
```

コードのチェックを実行。

```
$ composer run phpcs
```

## PSR-4 Autoloading Standard

* ファイルパスからクラスをオートロードするための仕様
  * [公式](http://www.php-fig.org/psr/psr-4/)
* Composerが対応している
  * `autoload`定義で指定

## PSR-7 HTTP Message Interface

* PHPでHTTPメッセージを扱うインターフェイス
  * [公式](http://www.php-fig.org/psr/psr-7/)
* グローバル変数触りまくりの世界から秩序のある世界へ
  * `$_GET`とか`$_POST`とか触らない
  * 不変オブジェクト
* SlimはPSR-7に従ったインターフェイスになっている


# その他

* PHPビルトインサーバ
  * アプリケーション開発の支援用サーバ
    * `$ php -S 0.0.0.0:1234 -t html`
    * [参考:PHP マニュアル/機能/コマンドラインの使用法/ビルトインウェブサーバー](http://php.net/manual/ja/features.commandline.webserver.php)
  * 公開サーバで使ってはだめ
  * `-t`オプションでドキュメントルートを指定
  * 便利リライト機能付き
    * URIリクエストにファイルが含まれない場合、指定ディレクトリにあるindex.phpあるいはindex.htmlを返す
      * どちらも存在しない場合は親ディレクトリにさかのぼってindex.phpとindex.htmlを探す
      * どちらか一方が見つかるか、あるいはドキュメントルートに達するまでこれが続く
  * 起動時にPHPファイルを指定すると、そのファイルをルータースクリプトとして扱う

* XDebug
  * PHPのリモートデバッガ
  * 拡張として入れてphp.iniで設定して有効化
    * ubuntuであれば`php-xdebug`パッケージとか入れる
    * php.iniに`xdebug.remote_enable=1`と`xdebug.remote_autostart=1`を足して使う
  * `var_dump`や`print_r`で済ませがちだけど便利

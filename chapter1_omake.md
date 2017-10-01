# つまづきポイント


## 論理演算子

```
<?php
$config = ["host" => "localhost"];

echo $config["port"] || 8080;
```

## foreach

```
<?php

$a = [
  ['name'=>'hoge', 'age' => 1],
  ['name'=>'fuga', 'age' => 2],
  ['name'=>'baz', 'age' => 3]
];

foreach($a as $b) {
  $b['age'] +=1;
}
print_r($a);
```

## switch

```
<?php

$a = '1';

switch ($a) {
  case 1:
    echo "number";
    break;
  case '1':
    echo "string";
    break;
  default:
    echo "no match";
}
```

# IPv4 Medoo 数据库支持

## 通过 composer 安装

```shell
composer require larryli/ipv4-medoo
```

## 使用

```php
$medoo = new \larryli\ipv4\medoo\Database([
    'database_type' => 'sqlite',
    'database_file' => __DIR__ . '/ipv4.sqlite',
]);
$monipdb = new \larryli\ipv4\MonIPDBQuery(__DIR__ . '/17monipdb.dat');
if (!$monipdb->exists()) {
    $monipdb->init();
}
$qqwry = new \larryli\ipv4\QQWryQuery(__DIR__ . '/qqwry.dat');
if (!$qqwry->exists()) {
    $qqwry->init();
}
$your_query = new \larryli\ipv4\FullQuery($medoo);
if (!$your_query->exists()) {
    $your_query->init(null, $monipdb, $qqwry);
}
$your_query->find(ip2long('127.0.0.1'));
```

## 相关包

* 核心 [larryli/ipv4](https://github.com/larryli/ipv4)
* Medoo 数据库支持 [larryli/ipv4-medoo](https://github.com/larryli/ipv4-medoo)
* Yii2 组件 [larryli/ipv4-yii2](https://github.com/larryli/ipv4-yii2)
* Yii2 示例 [larryli/ipv4-yii2-sample](https://github.com/larryli/ipv4-yii2-sample)

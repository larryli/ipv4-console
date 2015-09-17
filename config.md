# IPv4 控制台命令配置说明

默认配置文件为 ```~/.ipv4/ipv4.yaml```，采用 [yaml 格式](http://yaml.org/)。

## 数据库配置

数据库配置在 ```database``` 节，默认支持也只支持 Medoo 数据库驱动下 SQLite 和 MySQL 数据库。

```yaml
database:
  class: \larryli\ipv4\medoo\Database
  database_type: sqlite
  database_file: ~/.ipv4/ipv4.sqlite
```

其中 ```class``` 可以无需定义，但也可以指向其他驱动。```database_type``` 和 ```database_file``` 为 Medoo 配置数据，详见[相关文档](medoo.in/api/new)。

MySQL 可以如下配置：

```yaml
database:
  database_type: mysql
  database_name: ipv4
  server: localhost
  port: 3306  # optional
  username: homestead
  password: secret
  charset: utf8
```

## 数据源配置

数据源配置在 ```providers``` 节，可以配置多个。

```yaml
providers:
  monipdb:
    class: \larryli\ipv4\MonipdbQuery
    filename: ~/.ipv4/17monipdb.dat
```

当 ```class``` 为定义时，会自动根据名称 ```monipdb``` 猜测。

对于 ```\larryli\ipv4\FileQuery``` 需要定义 ```filename``` 内容。

如果无法找到文件，请尽量使用完整文件路径，比如 ```/home/larry/.ipv4/17monipdb.dat```

对于 ```\larryli\ipv4\ApiQuery``` 可以无需定义内容：

```yaml
providers:
  freeipip:
  taobao:
  sina:
  baidumap:
```

对于 ```\larryli\ipv4\DatabaseQuery``` 需要定义 ```providers``` 作为其初始化数据源。

```yaml
providers:
  monipdb:
    filename: ~/.ipv4/17monipdb.dat
  qqwry:
    filename: ~/.ipv4/qqwry.dat
  full:
    providers:
      - monipdb
      - qqwry
  mini:
    class: \larryli\ipv4\MiniQuery
    providers:
      - full
```

其中 ```providers``` 内容是已定义的其他数据源名称，可以为一个或多个；其中第一个为主数据源，其他是备选，按照定义顺序依次选用。

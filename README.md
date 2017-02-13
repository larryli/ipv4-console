# IPv4 控制台命令

## 通过 composer 安装

```shell
composer global require "larryli/ipv4-console=~1.0"
```

然后将 ```$HOME/.composer/vendor/bin``` 加入 ```$PATH``` 环境变量

## 初始化

```shell
ipv4 init
```

可以使用 ```--force``` 更新覆盖现有数据。

## 查询

```shell
ipv4 query 127.0.0.1
```

## 配置

```shell
ipv4 edit
```

请参阅 [Config 配置文档说明](config.md)

## 导出

```
ipv4 export --type qqwry monipdb 17monipdb.qqwry.dat
```

格式为：```--type``` 导出文件类型，然后是要导出的查询库和保存的文件。

目前支持 ```monipdb``` 和 ```qqwry``` 两种导出类型。

另外，可以使用 ```--encoding``` 指定导出文件的编码。

其中，```monipdb``` 默认编码是 ```utf-8```；```qqwry``` 默认编码是 ```gbk```。

对于，```monipdb``` 可选项有 ```--ecdz=1``` 设置导出的地址字符串中没有制表符分隔。

对于，```qqwry``` 可选项有 ```--remove-ip-in-recode=1``` 去掉冗余的记录区 IP 数据（对文件兼容性有影响，但可以大幅减少文件大小）。

## 杂项

```shell
ipv4 benchmark        # 性能测试
ipv4 clean            # 清除全部数据
ipv4 clean file       # 清除下载的文件数据
ipv4 clean database   # 清除生成的数据库数据
ipv4 dump             # 导出原始数据
ipv4 dump division    # 导出排序好的全部地址列表
ipv4 dump division_id # 导出排序好的全部地址和猜测行政区域代码列表
ipv4 dump count       # 导出记录统计数据
```

注意：```dump``` 命令会耗费大量内存，请配置 PHP ```memory_limit``` 至少为 ```128M``` 或更多。


## 相关包

* 核心 [larryli/ipv4](https://github.com/larryli/ipv4)
* Medoo 数据库支持 [larryli/ipv4-medoo](https://github.com/larryli/ipv4-medoo)
* Yii2 组件 [larryli/ipv4-yii2](https://github.com/larryli/ipv4-yii2)
* Yii2 示例 [larryli/ipv4-yii2-sample](https://github.com/larryli/ipv4-yii2-sample)

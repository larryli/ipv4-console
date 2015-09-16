# IPv4 控制台命令

## 通过 composer 安装

```shell
composer require larryli/ipv4-console
```

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

## 杂项

```shell
ipv4 benchmark        # 性能测试
ipv4 clean            # 清除全部数据
ipv4 clean file       # 清除下载的文件数据
ipv4 clean database   # 清除生成的数据库数据
ipv4 dump             # 导出原始数据
ipv4 dump division    # 导出排序好的全部地址列表
ipv4 dump division_id # 导出排序好的全部地址和猜测行政区域代码列表
ipv4 dump count       # 导出纪录统计数据
```

注意：```dump``` 命令会耗费大量内存，请配置 PHP ```memory_limit``` 至少为 ```128M``` 或更多。


## 相关包

* 核心 [larryli/ipv4](https://github.com/larryli/ipv4)
* Medoo 数据库支持 [larryli/ipv4-medoo](https://github.com/larryli/ipv4-medoo)
* Yii2 组件 [larryli/ipv4-yii2](https://github.com/larryli/ipv4-yii2)
* Yii2 示例 [larryli/ipv4-yii2-sample](https://github.com/larryli/ipv4-yii2-sample)

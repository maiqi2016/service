# 项目
喀客项目服务提供商

## 安装步骤

```shell
$ git clone https://github.com/maiqi2016/service.git
$ chmod a+x service/install.sh
```

### 本机环境

```shell
$ cd service
$ composer install
$ ./install.sh
$ cd thrift
$ nohup python runserver.py localhost 8888 &
```

### `Docker` 环境

```
$ sudo docker-compose up -d     # 并确保已经安装 `/web/docker` 并执行了 `/web/docker/script/` 目录下的所有脚本
$ mq-composer install --ignore-platform-reqs
$ mq-bash service/install.sh
$ mq-bash 进入容器后执行 (docker)$ /usr/local/init-service
```
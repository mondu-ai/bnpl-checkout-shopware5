
# Shopware 5 installation steps

## Content

This document contains shopware 5 preparation and plugin installation steps:

- [Dockerized](#dockerized)
- [Manual installation](#manual-installation)

# Dockerized

Content 

- [Docker container start](#docker-container-start)

## Docker container start

- Run in bash
    ```bash
    docker-compose up
    ```
**INFO**
In case of manual access will be needed to MySQL or SSH/SFTP
in docker-compose.yml expose their ports
```yml
ports:
	MYSQL_PORT:3306
	SSH_PORT:22
```

Shopware admin credentials
- Login: demo
- Password: demo

MySQL credentials
- User: shopware 
- Password: shopware 
- Port: 3306

SSH/SFTP credentials
- User: dockware 
- Password: dockware
- Port: 22

# Manual Installation

Content:

- [Mysql installation](#mysql-installations)
- [PHP installation](#php-installation)
- [Nginx installation](#nginx-installation)
- [Mysql, PHP, Nginx Configuration](#configuration)
- [Shopware5 installation](#shopware-5-installation-steps) 
- [Plugin installation](#bnpl-checkout-installation)
- [Plugin configuration](#bnpl-checkout-configuration)

## Mysql Installation
### Mac mysql installation
- Install Mysql via brew
    ```bash 
    brew install mysql
    ```
- To start mysql server in Daemon mode
    ```bash
    brew services start mysql
    ```
    - To stop server from Daemon mode
        ```bash
        brew services stop mysql
        ```
- To add root password
    ```bash
    mysql_secure_installation
    ```
    Go by steps, add root password, you can keep anonymous users for later testing purposes
- Free mode start/stop (Life cycle until reboot)
    ```bash
    mysql.server start
    mysql.server stop
    ```

## PHP installation
### Mac PHP installation

- Install Mysql via brew
    ```bash 
    brew install php
    ```
- To start php-fpm in Daemon mode
    ```bash
    brew services start php
    ```
    - To stop nginx from Daemon mode
        ```bash
        brew services stop php
        ```

## Nginx installation
### Mac Nginx installation

- Install Mysql via brew
    ```bash 
    brew install nginx
    ```

- To start nginx in Daemon mode
    ```bash
    brew services start nginx
    ```
    - To stop nginx from Daemon mode
        ```bash
        brew services stop nginx
        ```

## Configuration
### Mac configuration

- Mysql
Login into mysql
    ```bash
    mysql -u root -p
    ```

    Create database for shopware5
    ```mysql
    CREATE DATABASE shopware5
    ```

- PHP
In file /opt/homebrew/etc/php/8.2/php-fpm.d/www.conf **(in place of 8.2 put your version)** **_File location may differ depending on your configurations_**
Change
    ```conf
    user = _www
    group = _www
    listen = 127.0.0.1:9000
    ```
    to **(I will use 82 (version) in port, so will be able to use differenc version without interfering)**

    ```conf
    user = <your_username>
    group = staff
    listen = 127.0.0.1:9082
    ```
    Restart php-fpm
    ```bash
    brew services restart php
    ```

- Nginx
In file /opt/homebrew/etc/nginx/nginx.conf **_File location may differ depending on your configurations_**

    - Uncomment first line and change to 
        ```conf
        user <your_username> staff;
        ```
    - In block server, change this (You can change value of listen to change nginx port)
        ```conf
        listen 80;
        server_name  localhost;
        index index.html;
        ```
        to
        ```conf
        server_name  localhost test.x;
        index shopware.php index.html index.htm index.php;
        ```

    - Change location from 
        ```conf
        location / {
            root   html;
            index  index.html index.htm index.php;
        }
        ```
        to
        ```conf 
        location / {
            try_files $uri $uri/ /shopware.php$is_args$args;
        }
        ```

    - Add php-fpm location below _location /_ block
        ```conf 
        # Needed for Shopware install / update
        location /recovery/install {
            index index.php;
            try_files $uri /recovery/install/index.php$is_args$args;
        }

        location /recovery/update/ {
            if (!-e $request_filename){
                rewrite . /recovery/update/index.php last;
            }
        }

        location ~* ^.+\.(?:css|cur|js|jpe?g|gif|ico|png|svg|webp|html|woff|woff2|xml)$ {
            expires 1y;
            add_header Cache-Control "public, must-revalidate, proxy-revalidate";
        
            access_log off;
        
            # The directive enables or disables messages in error_log about files not found on disk.
            log_not_found off;
        
            tcp_nodelay off;
        
            ## Set the OS file cache.
            open_file_cache max=3000 inactive=120s;
            open_file_cache_valid 45s;
            open_file_cache_min_uses 2;
            open_file_cache_errors off;
        }

        location ~* ^.+\.svg$ {
            add_header Content-Security-Policy "script-src 'none'";
        }

        location ~ \.php$ {
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include  fastcgi_params;
            fastcgi_index shopware.php;
        
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            #include fastcgi.conf;
            fastcgi_buffers 8 16k;
            fastcgi_buffer_size 32k;
            client_max_body_size 24M;
            client_body_buffer_size 128k;
            fastcgi_pass 127.0.0.1:9082;
            #fastcgi_pass unix:/run/php/php7.2-fpm.sock;
            #http2_push_preload on;
        }
        ```
    - Restart nginx
        ```bash
        brew services restart nginx
        ```
    - Test nginx and php run
        ```bash
        mv /opt/homebrew/var/www/index.html /opt/homebrew/var/www/index.php
        ```
        Change /opt/homebrew/var/www/index.php file content to
        ```php
        <?php
        phpinfo();
        ?>
        ```
        Visit [nginx localhost](http://www:localhost:80) it should open php version and info page

## Shopware5 installation

- Download latest version of [Shopware5](https://www.shopware.com/en/changelog-sw5/) 
- Unzip files in nginx directory /opt/homebrew/var/www/index.php
- In browser open [nginx localhost](http://localhost:80/)

**Installation steps:**

- Choose language
- Check system requirements and push next
- Agree to terms of service
- Add Database user that you have created
    - Database server: localhost
    - Database user: root
    - Database password: <your_user_password>
    - Datanase name: shopware5
- Hit start installation
- Choose license 
- Shop set up
    - Name of your shop: Demoshop
    - E-mail address of the shop: your.email@shop.com
    - Main language: English (You can choose Deutsch)
    - Default currency: Euro
    - Admin name: Demo-Admin
    - Admin login name: demo
    - Admin e-mail: demo@demo.de
    - Admin password: adminadmin
- Go to shop Backend
- Login with demo account
    - Login: demo
    - Password: demo
    - Language: <choose preferred language>
- Hit next
- Add demodata in preferred language
- Skip PayPal installation
- Hit next in recommendation page
- Modify configurations
- Finish

## BNPL checkout installation


- Create plugin archive in repo
  - If you want to change sandbox from demo to stage
  In file Components/PluginConfig/Service/ConfigService.php modify 
    ```php
    const SANDBOX_API_URL = 'https://api.demo.mondu.ai/api/v1';
    const SANDBOX_WIDGET_URL = 'https://checkout.demo.mondu.ai/widget.js';
    ```
    to 
    ```php
    const SANDBOX_API_URL = 'https://api.stage.mondu.ai/api/v1';
    const SANDBOX_WIDGET_URL = 'https://checkout.stage.mondu.ai/widget.js';
    ```
  - Create a release which need ot be uploaded to shopware
    ```bash
    ./releaser.sh -v <your_version>
    ```
    
- Go to [Shopware5 backend](http://localhost:80/backend/)
    - Login: demo
    - Password: demo

- Hit configuration => plugin manager => installed (left side bar)
Hit Upload Plugin in
![](https://i.ibb.co/5RVNJWZ/Untitled.png)

- In modal select your plugin zip file

## BNPL checkout configuration

- Go to [Shopware5 backend](http://localhost:80/backend/)
    - Login: demo
    - Password: demo

- Hit configuration => plugin manager => installed (left side bar)
- Hit plus button on mondu plugin line
![](https://i.ibb.co/2M6czrt/Untitled.png)
Install plugin, after in plugin configuration
Enter your API code, and hit validate
![](https://i.ibb.co/9yvb194/Untitled.png)

- Go to configuration => Shipping Cost
In payment method submenu find mondu payment methods and push them to right
![](https://i.ibb.co/9T9LN6D/Untitled.png)

- Go to configuration => Basic settings **(Optional you may need this step if you can't see germany in shipping address)**
  - Select countries in shop settings
  - Find Germany and activate it
 
  ![](https://i.ibb.co/9T9LN6D/Untitled.png)

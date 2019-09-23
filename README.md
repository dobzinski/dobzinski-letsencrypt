# dobz-letsencrypt
Dobzinski's project for Let's Encrypt server to auto deploy of certs.

## Technologies (client/server)
* Non database required!
* Apache + Php + Json
* Clients using Shell script
* Certificates Server will need aprove clients by symmetric keys
* jQuery 3.3.1 + Popper 1.14.7
* Font Awesome 4.7.0
* Bootstrap 4.3.1

## Server Requiremets
* Linux CentOS/Debian
* Certbot
* OpenSSL
* Apache 2 + SSL
* Php 5 or higher
* Shell script (curl, md5sum, file, cut, tar, others)

## Clients Requiremets
* Linux
* Shell script (curl, md5sum, file, cut, tar, others)
* OpenSSL

## Screenshot
![Dashboard](https://github.com/dobzinski/dobz-letsencrypt/blob/master/images/screen-clean.png)

## Server Install

###### 1. Packages (simple configure)

> CentOS 7:
```
    # yum install certbot openssl httpd mod_ssl php git
    # systemctl enable httpd.service
    # systemctl start httpd.service
```

> Debian 10:
```
    # apt-get install certbot openssl apache2 php git
    # a2enmod ssl
    # a2ensite default-ssl
    # service apache2 reload
```

## Very Important!
> **Dot not use HTTP (Port 80), remember you will transfer keys from client to server, and will get Letsencrypt certificates Public and Private from this server. Insert those lines to Apache for redirect all requests to HTTPS (Port 443)**
```
    [...]
    # Redirect to HTTPS
    RewriteEngine On 
    RewriteCond %{HTTPS} !=on 
    RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L]
```
_Notes:_
- Install mod_rewrite, and insert lines on "httpd.conf" (CentOS) or "000-default.conf" (Debian) to redirect
- For Debian, Use "a2enmod rewrite" to enable mod_rewrite
- For CentOS, check the Firewall and SELinux

###### 2. Generate the new certificate with DNS Challenge
```
    # certbot certonly --manual --preferred-challenges dns
```
**TIP:** Anwser the question for the domain *.domain.com (wildcard) and when see key value generated, WAIT! DO NOT PRESS ENTER KEY. Go to the external DNS and add the new TXT entry \_acme-challenge.domain.com with the key value and insert "1" to TTL. Back to the terminal and press ENTER, you will see the sucess message!_

###### 3. Configure (with root login)

_NOTE: if you using Debian, remember to change user "apache" to "www-data" on bellow commands lines..._
```
    # git clone https://github.com/dobzinski/dobz-letsencrypt
    # chown -R apache. ./dobz-letsencrypt/server
    # mv ./dobz-letsencrypt/server/letsencrypt /var/www/html/
    # chown -R root. ./dobz-letsencrypt/server/cert
    # chown -R apache. ./dobz-letsencrypt/server/cert/certificate
    # chown -R apache. ./dobz-letsencrypt/server/cert/client
    # chmod +x ./dobz-letsencrypt/server/cert/letsencrypt.sh
    # mv ./dobz-letsencrypt/server/cert /opt/
```

###### 4. Configure the Enviroment Vars
```
    # vi /var/www/html/letsencrypt/config.php
```
_Customize: days left to alert, date/time format, theme, language ..._

###### 5. Configure the Update script

Edit the script "letsencrypt.sh" and change the "LIST" Array.
```
    # vi /opt/cert/letsencrypt.sh
    [...]
    LIST=("domain1.com" "domain2.com")
```

Add the script on root cron
```
    # contrab -e
    0 21 *  *  * /opt/cert/letsencrypt.sh
```

Or use the crontab file
```
    # vi /etc/crontab (add on last line)
    0 21 *  *  * root /opt/cert/letsencrypt.sh
```

## Client Install (LINUX)

###### 1. Requirements

Check the requirements, but will maybe only need OpenSSL

###### 2. Transfer the agent folder from Server to Client

Back to root path when was run "git clone"
```
    # scp -r ./dobz-letsencrypt/client/agent {USER}@{CLIENT}:/tmp/
```

###### 3. After login the Client, move the folder to /opt/
```
    # mv /tmp/agent /opt/
    # chown -R root. /opt/agent
    # chmod +x /opt/agent/check.sh
    # chmod +x /opt/agent/update.sh
```

###### 4. Change "VARS" in Scripts
```
    # cd /opt/agent
    # vi check.sh
    # vi update.sh
```

###### 5. Insert the reload/restart service to update the new certificate
```
    # vi update.sh
```
Go to last lines and insert the command between lines...
```
    # RUN COMMANDS
        {INSERT YOUR COMMANDS HER}
    # END COMMANDS
```

**Examples:**

- Apache/Ngix:
```
    # /bin/systemctl restart httpd.service >/dev/null 2>&1
    # /bin/systemctl reload nginx.service >/dev/null 2>&1
    # /usr/bin/killall -9 nginx && /sbin/service nginx start >/dev/null 2>&1
    # /etc/init.d/httpd restart >/dev/null 2>&1
```

- BIGIP F5 (First you need to uncomment BIGIP VARS):
```
    # /usr/bin/tmsh install /sys crypto cert ${CERTNAMEBIGIP} from-local-file ${ENABLE}/${NAME}/${FILE}
    # /usr/bin/tmsh install /sys crypto key ${CERTNAMEBIGIP} from-local-file ${ENABLE}/${NAME}/${KEY}
    # /usr/bin/tmsh save /sys config
    # /usr/bin/tmsh run /cm config-sync to-group ${DG}
```

###### 6. Add on crontab
```
    # vi /etc/crontab (add on last line)
    0 */6 *  *  * root /opt/agent/check.sh
    0 22 *  *  * root /opt/agent/update.sh
```
_Or add on root cron (contrab -e)_

###### 7. How to use (If was not changed paths in VARS)
1. After changed VARS in check.sh, run the first time to install client on server
2. Go to the server and check the file was created: /opt/cert/client/{IP}.json
3. Edit the file, change the "enable" to "true" and check the host name by reverse DNS, if not, change manually
4. Back to client and run again the check.sh, note the folder with name of certificate are criated in /opt/agent/
5. After changed VARS in update.sh, run script and the new folder "letsencypt" are created in /opt/agent/
6. If you use the Apache or Nginx, edit the config file and set the path /opt/agent/letsencrypt to public key (fullchain.pem) and private key (privkey.pem) 
7. If you have multiples domains in your client server, you need to replicate scripts "check.sh" and "update.sh" to "check-domain1.sh", "check-domain2.sh", "update-domain1.sh", "update-domain2.sh" ... change "VARS" in scripts and you will need replicate the cron jobs

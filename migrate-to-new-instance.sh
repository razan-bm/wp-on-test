#!/usr/bin/env bash

# In existing instance:
new_instance_ip="13.210.79.243"
migration_dir="migration"
mkdir -p ~/$migration_dir
ssh-keygen -q -t rsa -N '' -f ~/.ssh/id_rsa <<<y >/dev/null 2>&1
cat ~/.ssh/id_rsa.pub

# New instance:
echo "pubkey" | tee -a /home/bitnami/.ssh/authorized_keys

# Back to existing instance:
cd /opt/bitnami/wordpress
node -v | awk -F'[v.]' '{print $2}' > ~/$migration_dir/node_version
git remote -v | awk 'NR==1 {print $2}' > ~/$migration_dir/git_url
awk -F "['\"]" '/\$table_prefix/ { print $2 }' wp-config.php > ~/$migration_dir/table_prefix
table_prefix=$(cat ~/$migration_dir/table_prefix)
awk -F"[\"']" '/define\(( |\t)*'"'"'DB_NAME'"'"',( |\t)*[\"'\'']/ {print $4}' wp-config.php > ~/$migration_dir/database_name
sudo chown -R bitnami:daemon .
git add .
git commit -m "Commit changes before migrating to new instance"
git push
mariadb-dump -u root -p$(cat ~/password) $(cat ~/$migration_dir/database_name) > ~/$migration_dir/database.sql
mariadb -u root -p$(cat ~/password) $(cat ~/$migration_dir/database_name) -e "SELECT option_value FROM ${table_prefix}options WHERE option_name='siteurl'" -N > ~/$migration_dir/url
cp ./wp-config.php ~/$migration_dir/wp-config.php
sudo cp /opt/bitnami/apache/conf/vhosts/htaccess/wordpress-htaccess.conf ~/$migration_dir/wordpress-htaccess.conf
sudo cp /opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf ~/$migration_dir/wordpress-https-vhost.conf
sudo cp /opt/bitnami/apache/conf/vhosts/wordpress-vhost.conf ~/$migration_dir/wordpress-vhost.conf
sudo chown bitnami:bitnami ~/$migration_dir/wordpress-htaccess.conf
sudo chown bitnami:bitnami ~/$migration_dir/wordpress-https-vhost.conf
sudo chown bitnami:bitnami ~/$migration_dir/wordpress-vhost.conf
ssl_cert_file=$(grep 'SSLCertificateFile' /opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf | sed -E 's/.*SSLCertificateFile "([^"]+)".*/\1/')
ssl_cert_file_name=$(basename "$ssl_cert_file")
ssl_cert_key_file=$(grep 'SSLCertificateKeyFile' /opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf | sed -E 's/.*SSLCertificateKeyFile "([^"]+)".*/\1/')
ssl_cert_key_file_name=$(basename "$ssl_cert_key_file")
sudo cp "$ssl_cert_file" ~/$migration_dir/"$ssl_cert_file_name"
sudo cp "$ssl_cert_key_file" ~/$migration_dir/"$ssl_cert_key_file_name"
sudo chown bitnami:bitnami ~/$migration_dir/"$ssl_cert_file_name"
sudo chown bitnami:bitnami ~/$migration_dir/"$ssl_cert_key_file_name"
cd ./wp-content
zip -r ~/$migration_dir/uploads.zip ./uploads

# Copy files to new instance
ssh-keyscan -H $new_instance_ip >> ~/.ssh/known_hosts
scp -r ~/$migration_dir bitnami@$new_instance_ip:~/

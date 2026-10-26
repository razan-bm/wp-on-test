#!/usr/bin/env bash
node_version="20"
git_url="https://<username>:<accesstoken>@github.com/what-works-global/<reponame>.git"
table_prefix="wp_"
database_name="wp_base"
url="https://base.com.au"


migration_dir="$HOME/migration"
wp_dir="/opt/bitnami/wordpress"
if [[ -s "$migration_dir/node_version" ]]; then
  node_version=$(cat $migration_dir/node_version)
fi
if [[ -s "$migration_dir/git_url" ]]; then
  git_url=$(cat $migration_dir/git_url)
fi
if [[ -s "$migration_dir/table_prefix" ]]; then
  table_prefix=$(cat $migration_dir/table_prefix)
fi
if [[ -s "$migration_dir/database_name" ]]; then
  database_name=$(cat $migration_dir/database_name)
fi
if [[ -s "$migration_dir/url" ]]; then
  url=$(cat $migration_dir/url)
fi
if [[ -s "$HOME/password" ]]; then
  pass=$(cat $HOME/password)
fi
if [[ -s "$HOME/bitnami_application_password" ]]; then
  pass=$(cat $HOME/bitnami_application_password)
fi

mv ~/bitnami_application_password ~/password
sudo apt-get -y install git
git config --global alias.lola "log --graph --decorate --pretty=oneline --abbrev-commit --all"
git config --global user.email "test@localhost.com"
git config --global user.name "Lightsail Instance"
cd /opt/bitnami
sudo mv wordpress wordpress-old
sudo git clone $git_url wordpress
# If the repository has a wordpress subfolder,
# point the apache server to that subolder instead of the root folder
if [ -d "$wp_dir/wordpress" ]; then
  conf_dir="/opt/bitnami/apache/conf/vhosts"
  sudo chown bitnami:daemon $wp_dir
  sudo chmod 775 $wp_dir
  sudo sed -i 's|bitnami/wordpress|bitnami/wordpress/wordpress|g' "$conf_dir/wordpress-vhost.conf"
  sudo sed -i 's|bitnami/wordpress|bitnami/wordpress/wordpress|g' "$conf_dir/wordpress-https-vhost.conf"
  wp_dir+="/wordpress"
fi
echo "alias wpd='cd $wp_dir'" >> ~/.bashrc
echo "alias theme='cd $wp_dir/wp-content/themes/wp-theme'" >> ~/.bashrc
echo "alias debug='less $wp_dir/wp-content/debug.log'" >> ~/.bashrc
echo "alias vhosts='cd /opt/bitnami/apache/conf/vhosts'" >> ~/.bashrc
echo "alias logs='cd /opt/bitnami/apache/logs'" >> ~/.bashrc
echo "alias restart='sudo /opt/bitnami/ctlscript.sh restart apache && sudo /opt/bitnami/ctlscript.sh restart php-fpm'" >> ~/.bashrc
echo "" >> ~/.bashrc
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.5/install.sh | bash
source ~/.bashrc
nvm install $node_version
nvm use $node_version
sudo chown -R bitnami:daemon wordpress
if [[ -e "$migration_dir/wordpress-htaccess.conf" ]]; then
  sudo cp $migration_dir/wordpress-htaccess.conf /opt/bitnami/apache/conf/vhosts/htaccess/wordpress-htaccess.conf
  sudo chown root:root /opt/bitnami/apache/conf/vhosts/htaccess/wordpress-htaccess.conf
else
  echo -e "\n# Deny access to .git folder (prevents bots spamming server with requests)\n<IfModule alias_module>\n  RedirectMatch 404 /\\.git\n  RedirectMatch 404 (.*)\.(env|sh)$\n</IfModule>" | sudo tee /opt/bitnami/apache/conf/vhosts/htaccess/wordpress-htaccess.conf
fi

if [[ -e "$migration_dir/wordpress-vhost.conf" ]]; then
  # Extract server name from vhost file
  server_name=$(grep 'ServerName' $migration_dir/wordpress-vhost.conf | awk '{print $2}')
  # Make backup of current vhost file
  sudo cp /opt/bitnami/apache/conf/vhosts/wordpress-vhost.conf "/opt/bitnami/apache/conf/vhosts/wordpress-vhost.conf.back.$(date +"%Y%m%d%H%M%S")"
  # Edit ServerName property in vhost file
  sudo sed -i "s/ServerName .*/ServerName $server_name/" /opt/bitnami/apache/conf/vhosts/wordpress-vhost.conf
fi

if [[ -e "$migration_dir/wordpress-https-vhost.conf" ]]; then
  # Extract server name from https vhost file
  server_name=$(grep 'ServerName' $migration_dir/wordpress-https-vhost.conf | awk '{print $2}')
  # Make backup of current https vhost file
  sudo cp /opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf "/opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf.back.$(date +"%Y%m%d%H%M%S")"
  # Edit ServerName property in https vhost file
  sudo sed -i "s/ServerName .*/ServerName $server_name/" /opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf
  
  # Extract ssl cert and key file paths from https vhost file
  ssl_cert_file=$(grep 'SSLCertificateFile' $migration_dir/wordpress-https-vhost.conf | sed -E 's/.*SSLCertificateFile "([^"]+)".*/\1/')
  ssl_cert_file_name=$(basename "$ssl_cert_file")
  ssl_cert_key_file=$(grep 'SSLCertificateKeyFile' $migration_dir/wordpress-https-vhost.conf | sed -E 's/.*SSLCertificateKeyFile "([^"]+)".*/\1/')
  ssl_cert_key_file_name=$(basename "$ssl_cert_key_file")
  # Copy old instance ssl cert and key files to bitnami certs directory
  ssl_cert_file_destination="/opt/bitnami/apache/conf/bitnami/certs/$ssl_cert_file_name"
  ssl_cert_key_file_destination="/opt/bitnami/apache/conf/bitnami/certs/$ssl_cert_key_file_name"
  sudo cp $migration_dir/$ssl_cert_file_name $ssl_cert_file_destination
  sudo cp $migration_dir/$ssl_cert_key_file_name $ssl_cert_key_file_destination
  sudo chmod 600 $ssl_cert_key_file_destination
  # Edit SSLCertificateFile and SSLCertificateKeyFile properties in https vhost file
  sudo sed -i "s|SSLCertificateFile .*|SSLCertificateFile \"$ssl_cert_file_destination\"|" /opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf
  sudo sed -i "s|SSLCertificateKeyFile .*|SSLCertificateKeyFile \"$ssl_cert_key_file_destination\"|" /opt/bitnami/apache/conf/vhosts/wordpress-https-vhost.conf
fi


if [[ -e "$migration_dir/uploads.zip" ]]; then
  cd $wp_dir/wp-content
  cp $migration_dir/uploads.zip .
  unzip uploads.zip
  rm uploads.zip
fi

if [[ -e "$migration_dir/database.sql" ]]; then
  result=$(mariadb -u root -p$pass -e "SHOW DATABASES" | grep $database_name)
  # if database does not exist
  if [[ "$result" != "$database_name" ]]; then
    mariadb -u root -p$pass -e "CREATE DATABASE ${database_name};"
    mariadb -u root -p$pass $database_name < "$migration_dir/database.sql"
  fi
  mariadb -u root -p$pass $database_name -e "UPDATE ${table_prefix}options SET option_value='http://$(ec2metadata --public-ipv4)' WHERE option_name='siteurl' OR option_name='home';"
fi


cd $wp_dir
if [[ -e "$migration_dir/wp-config.php" ]]; then
  cp $migration_dir/wp-config.php ./wp-config.php
else
  cp wp-config-sample.php wp-config.php
  sed -i "/define(\s*['\"]DB_NAME['\"]\s*,\s*['\"]/s/['\"][^'\"]*['\"]/\'$database_name\'/2" wp-config.php
  sed -i "/define(\s*['\"]DB_USER['\"]\s*,\s*['\"]/s/['\"][^'\"]*['\"]/\'root\'/2" wp-config.php
fi

# Replace DB_PASSWORD in wp-config.php with the password in ~/password
escaped_password=$(sed -e 's/[\/&]/\\&/g' ~/password)
sed -i "/define(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]/s/['\"][^'\"]*['\"]/\'$escaped_password\'/2" wp-config.php
sudo bash set-correct-file-permissions.sh
theme
npm install && npm run build


# --- Create a swap file ---

# Get available memory in megabytes
total_mem=$(free -m | awk '/^Mem:/{print $2}')

# Calculate the first power of 2 greater than or equal to total_mem
nearest_power_of_2=1
while [ "$nearest_power_of_2" -lt "$total_mem" ]; do
  nearest_power_of_2=$((nearest_power_of_2 * 2))
done

# If memory < 4GB, SWAPSIZE = double memory, else SWAPSIZE = memory
if [ "$nearest_power_of_2" -lt 4096 ]; then
  SWAPSIZE=$((nearest_power_of_2 * 2))
else
  SWAPSIZE=$nearest_power_of_2
fi

# Define the name of the swap file
SWAPFILE="/swapfile"

# Create a swap file of the calculated size using dd
sudo dd if=/dev/zero of="$SWAPFILE" bs=1M count="$SWAPSIZE"
sudo chmod 600 "$SWAPFILE"
sudo mkswap "$SWAPFILE"
sudo swapon "$SWAPFILE"

# Create backup of /etc/fstab
sudo cp /etc/fstab /etc/fstab.bak

# Add the swap file to /etc/fstab to make it permanent
echo "$SWAPFILE none swap sw 0 0" | sudo tee -a /etc/fstab

# Restart apache
sudo /opt/bitnami/ctlscript.sh restart





######################################################
######################################################
######################################################
######################################################
######################################################
######################################################

# ---- STOP HERE AND CHECK EVERYTHING IS WORKING ----

# After pointing domain to lightsail instance, run:
sudo /opt/bitnami/bncert-tool
mariadb -u root -p$pass $database_name -e "UPDATE ${table_prefix}options SET option_value='$url' WHERE option_name='siteurl' OR option_name='home';"
(echo -e "# Ping the server every 5 minutes, restart apache server if request fails\n*/5 * * * * curl -sSfI $url 1>>/dev/null 2>>/home/bitnami/cron.log || (printf \"\$(date)\\\n\$(TZ=Australia/Sydney date)\\\n\" >> /home/bitnami/cron.log && /opt/bitnami/ctlscript.sh restart >> /home/bitnami/cron.log)") | sudo crontab -


######################################################
######################################################
######################################################
######################################################
######################################################
######################################################

# ---- OPTIONAL ----

# Remove any files that are not needed anymore
rm -rf $migration_dir

# Check home and siteurl in database
mariadb -u root -p$pass $database_name -e "SELECT option_name, option_value FROM ${table_prefix}options WHERE option_name='siteurl' OR option_name='home';"
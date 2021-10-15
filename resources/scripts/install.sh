#!/usr/bin/env bash

#
# Important! This script needs to be written in a way that we can run it multiple times without causing harm.
# Test all changes by running the script twice or more to see that it works this way.
#

# enable this for debug
#set -x

# we figure this out by magic
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"  # (this)
ROOT_DIR="$(readlink -f "$SCRIPT_DIR/../../..")"   # (this)/../../..

# root is not acceptable to use to run this script
if [[ $EUID -eq 0 ]]; then
    echo "This script should not be run as root" 1>&2
    exit 1
fi

# parse parameters
PLATFORM=prod
while [[ $# -gt 0 ]]; do
    case "$1" in
        --platform=*)
            PLATFORM="${1#*=}"
            case ${PLATFORM} in
                prod|docker)
                ;;

                *)
                    echo "Invalid value for --platform: ${PLATFORM}"
                    exit 1
                ;;
            esac
            ;;


        *)
            echo "***************************\n"
            echo "* Error: Invalid argument.*\n"
            echo "***************************\n"
            exit 1
    esac
    shift
done

if [[ "$PLATFORM" == "docker" ]]; then
    # in docker we have copied all the resources to /install
    RESOURCES_DIR="/install"
else
    # in prod we use /var/www/sirvoy-project/resources
    RESOURCES_DIR="${ROOT_DIR}/resources"
fi

sudo apt-get update > /dev/null


echo "--> Fixing tzdata to be noninteractive..."
sudo ln -fs /usr/share/zoneinfo/UTC /etc/localtime > /dev/null 2>&1
sudo apt-get install -y tzdata
sudo dpkg-reconfigure -f noninteractive tzdata

# install awscli and awslogs, and add-apt-repository (software-properties-common), and curl
sudo apt-get -y install python3-pip python3-software-properties software-properties-common curl 
#> /dev/null 2>&1
sudo pip3 install --upgrade pip 
#> /dev/null 2>&1

# ondrej/php repository
#if [[ ! -f /etc/apt/sources.list.d/ondrej-ubuntu-php-focal.list ]]; then
#    echo "--> Adding ondrej/php repository for more php versions..."
#    # https://github.com/oerdnj/deb.sury.org/issues/56
#    # add php repository: https://launchpad.net/~ondrej/+archive/ubuntu/php
#    sudo LC_ALL=en_US.UTF-8 add-apt-repository --yes ppa:ondrej/php > /dev/null
#fi

sudo apt-get update > /dev/null


# install packages that are needed
echo "--> Installing LAMP stack..."
PHP_VERSION="7.4"
sudo apt-get -y install acl apache2 libapache2-mod-php libapache2-mod-security2 php${PHP_VERSION} php${PHP_VERSION}-apcu \
                        php${PHP_VERSION}-curl php${PHP_VERSION}-gd php${PHP_VERSION}-intl php${PHP_VERSION}-imap \
                        php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring  php${PHP_VERSION}-soap \
                        php${PHP_VERSION}-xml php${PHP_VERSION}-zip php${PHP_VERSION}-gettext php${PHP_VERSION}-bcmath \
                        git unzip libwww-perl libdatetime-perl python mc htop mysql-client 
                        #> /dev/null 2>&1

# configure apache2
#echo "--> Disabling apache2 modules: autoindex, php7.0, php7.1, php7.2, php7.3, php7.4..."
#sudo a2dismod -f autoindex > /dev/null
#sudo a2dismod php7.0 > /dev/null 2>&1
#sudo a2dismod php7.1 > /dev/null 2>&1
#sudo a2dismod php7.2 > /dev/null 2>&1
#sudo a2dismod php7.3 > /dev/null 2>&1
#sudo a2dismod php7.4 > /dev/null 2>&1

# Set up the volume map we can deveop our app in
echo "--> Set up the volume map we can deveop our app in.."
sudo ln -fs /docker/app/www-root/ /var/www/html/app

echo "--> Enabling apache2 modules: rewrite, headers, php8.0..."
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod php7.4

# set defaults cli commands
echo "--> Setting php ${PHP_VERSION} to be the default version for php..."
sudo update-alternatives --set php /usr/bin/php${PHP_VERSION}

# configure php
echo "--> Enabling php modules: imap..."
sudo phpenmod imap 
#> /dev/null 2>&1

#echo "--> Installing apache2 vhost config..."
#sudo cp "${RESOURCES_DIR}/config/etc/apache2/sites-available/sirvoy-project.conf" /etc/apache2/sites-available/sirvoy-project.conf

#echo "--> Installing apache2 security.conf..."
#sudo cp "${RESOURCES_DIR}/config/etc/apache2/conf-available/security.conf" /etc/apache2/conf-available/security.conf
#echo "--> Installing apache2 ports.conf..."
#sudo cp "${RESOURCES_DIR}/config/etc/apache2/ports.conf" /etc/apache2/ports.conf

#echo "--> Installing php custom config for apache2..."
#sudo cp "${RESOURCES_DIR}/config/etc/php/7.X/apache2/conf.d/99-custom.ini" /etc/php/${PHP_VERSION}/apache2/conf.d/99-custom.ini
#echo "--> Installing php custom config for CLI..."
#sudo cp "${RESOURCES_DIR}/config/etc/php/7.X/cli/conf.d/99-custom.ini" /etc/php/${PHP_VERSION}/cli/conf.d/99-custom.ini


#echo "--> Enabling vhost and disabling the default vhost..."
#sudo a2dissite 000-default.conf > /dev/null
#sudo a2ensite sirvoy-project > /dev/null

# install composer
echo "--> Installing composer (global)..."
# download a specific version that we can checksum
curl -o /tmp/composer.phar https://getcomposer.org/download/2.1.3/composer.phar 
#> /dev/null 2>&1
sha256sum /tmp/composer.phar | grep f8a72e98dec8da736d8dac66761ca0a8fbde913753e9a43f34112367f5174d11 
#> /dev/null 2>&1
if [[ $? != 0 ]] ; then
    echo -e "ERROR: Checksum of downloaded composer.phar does not match - possibly corrupted, please try to run the script again!"
    sudo rm /tmp/composer.phar
    exit 1
fi
# do self-update to get the latest, as we trust composer to verify the checksums
chmod a+x /tmp/composer.phar > /dev/null 2>&1
/tmp/composer.phar selfupdate
# move it in place
sudo mv /tmp/composer.phar /usr/local/bin/composer

# install font dependencies (needed by wkhtmltopdf)
# sudo apt-get -y install fontconfig libfontenc1 libxrender1 x11-common xfonts-75dpi xfonts-base xfonts-encodings xfonts-utils 
#> /dev/null 2>&1

# install wkhtmltopdf (if it isn't already installed)
#if [[ ! -f /usr/local/bin/wkhtmltopdf ]]; then
#    # http://wkhtmltopdf.org/downloads.html
#    echo "--> Installing wkhtmltopdf 0.12.6 ..."
#
#    sudo dpkg -i "${RESOURCES_DIR}/third-party/dist/wkhtmltox_0.12.6-1.focal_amd64.deb" > /dev/null 2>&1
#fi

echo "--> Remove previous apache pid - fix for exit 137..."
sudo rm -f /var/run/apache2/apache2.pid

# now restart apache
echo "--> now restart apache.."
sudo service apache2 restart
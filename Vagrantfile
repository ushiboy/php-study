# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.box = "box-cutter/ubuntu1604"
  config.vm.network "forwarded_port", guest: 80, host: 9090
  # config.vm.network "private_network", ip: "192.168.33.10"
  # config.vm.network "public_network"
  # config.vm.provider "virtualbox" do |vb|
  #   # Display the VirtualBox GUI when booting the machine
  #   vb.gui = true
  #
  #   # Customize the amount of memory on the VM:
  #   vb.memory = "1024"
  # end

  config.vm.provision "shell", inline: <<-SHELL
    sed -i.bak -e "s%http://us.archive.ubuntu.com%http://jp.archive.ubuntu.com%g" /etc/apt/sources.list
    apt-get update
    apt-get install -y language-pack-ja
    update-locale LANG=ja_JP.UTF-8
    apt-get install -y apt-file
    apt-file update
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update
    apt-get install -y php7.1-fpm php7.1-mbstring php7.1-xml php7.1-zip unzip cmake build-essential libssl-dev zlib1g-dev postgresql php7.1-pgsql composer nginx tmux htop apache2-utils python3-pip python3-venv
    cp /vagrant/assets/etc/nginx/sites-available/default /etc/nginx/sites-available/default
    service nginx restart
    cp -r /vagrant/assets/var/sample /var
    cd /var/sample/python
    python3 -m venv venv
    venv/bin/pip install -r requirements.txt
  SHELL
end

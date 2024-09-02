FROM debian:12

RUN apt-get update && apt-get install -y supervisor
RUN apt-get update && apt-get install -y vim jq nano less virt-what net-tools procps curl unzip mariadb-server python3-dev default-libmysqlclient-dev build-essential pkg-config

RUN curl -fsSL -o jdk-21_linux-x64_bin.deb https://download.oracle.com/java/21/latest/jdk-21_linux-x64_bin.deb
RUN echo "$(curl -s https://download.oracle.com/java/21/latest/jdk-21_linux-x64_bin.deb.sha256) jdk-21_linux-x64_bin.deb" | sha256sum -c
RUN dpkg -i jdk-21_linux-x64_bin.deb
RUN rm -rf jdk-21_linux-x64_bin.deb

RUN curl -fsSL -o keycloak-25.0.5.zip https://github.com/keycloak/keycloak/releases/download/25.0.5/keycloak-25.0.5.zip
RUN echo "$(curl -fsSL https://github.com/keycloak/keycloak/releases/download/25.0.5/keycloak-25.0.5.zip.sha1) keycloak-25.0.5.zip" | sha1sum -c
RUN unzip keycloak-25.0.5.zip
RUN mv keycloak-25.0.5 /opt/
RUN rm keycloak-25.0.5.zip

RUN curl -fsSL -o /etc/apt/keyrings/salt-archive-keyring-2023.gpg https://repo.saltproject.io/salt/py3/debian/12/amd64/SALT-PROJECT-GPG-PUBKEY-2023.gpg
RUN echo "deb [signed-by=/etc/apt/keyrings/salt-archive-keyring-2023.gpg arch=amd64] https://repo.saltproject.io/salt/py3/debian/12/amd64/latest bookworm main" | tee /etc/apt/sources.list.d/salt.list
RUN apt-get update && apt-get install -y salt-minion

RUN /opt/saltstack/salt/bin/python3 -m pip install --upgrade pip
RUN /opt/saltstack/salt/bin/python3 -m pip install mysql
RUN apt-get update && apt-get remove -y python3-dev default-libmysqlclient-dev build-essential pkg-config

COPY conf/keycloak-startup.conf /etc/supervisor/conf.d
COPY conf/mysql.conf /etc/supervisor/conf.d
COPY conf/salt-minion.conf /etc/supervisor/conf.d
COPY conf/mysql_starter.sh /root/
COPY conf/startup.conf /etc/supervisor/conf.d
COPY conf/minion.cnf /etc/salt/minion
COPY conf/salt-startup.sh /root
COPY conf/init.sql /root

RUN chmod +x /root/mysql_starter.sh
RUN chmod +x /root/salt-startup.sh

COPY conf/run-supervisor.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/run-supervisor.sh
CMD ["/usr/local/bin/run-supervisor.sh"]
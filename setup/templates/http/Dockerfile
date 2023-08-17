FROM debian:11

RUN apt-get update && apt-get install -y supervisor salt-minion
RUN apt-get update && apt-get install -y cron apache2 mc vim nano less libapache2-mod-php7.4 php7.4-cli apache2-utils

COPY conf/000-default.conf /etc/apache2/sites-available/
COPY conf/run-supervisor.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/run-supervisor.sh
CMD ["/usr/local/bin/run-supervisor.sh"]
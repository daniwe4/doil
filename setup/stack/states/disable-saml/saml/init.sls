{% set doil_domain = salt['grains.get']('doil_domain') %}

jq.package:
  pkg.installed:
    - name: jq

/var/ilias/data/ilias/auth/saml/config/authsources.php:
  file.absent

/var/ilias/data/ilias/auth/saml/config/config.php:
  file.absent

/var/ilias/cert/saml.crt:
  file.absent

/var/ilias/cert/saml.pem:
  file.absent

/var/www/html/DeleteIdp.php:
  file.managed:
    - source: salt://saml/DeleteIdp.php.j2
    - template: jinja
    - context:
      ilias_http_path: {{ doil_domain }}
      keycloak_host_name: %TPL_KEYCLOAK_HOSTNAME%
    - user: root
    - group: root
    - mode: 644

delete_ilias_idp:
  cmd.run:
    - name: php DeleteIdp.php
    - cwd: /var/www/html
    - watch:
        - file: /var/www/html/DeleteIdp.php

/root/deleteInstanceFromKeycloak.sh:
  file.managed:
    - source: salt://saml/deleteInstanceFromKeycloak.sh.j2
    - template: jinja
    - context:
      server_host_name: %TPL_KEYCLOAK_HOSTNAME%
      admin_password: %TPL_ADMIN_PASSWORD%
      doil_domain: {{ doil_domain }}
    - user: root
    - group: root
    - mode: 744

deleteInstanceFromKeycloak:
  cmd.run:
    - name: ./deleteInstanceFromKeycloak.sh
    - cwd: /root
    - runas: root
    - require:
        - file: /root/deleteInstanceFromKeycloak.sh

delete_DeleteIdp.php:
  file.absent:
    - name: /var/www/html/DeleteIdp.php
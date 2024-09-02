{% set doil_domain = salt['grains.get']('doil_domain') %}
{% set mpass = salt['grains.get']('mpass') %}
{% set samlpass = salt['grains.get']('samlpass', 'abcdef123456!!$') %}
{% set samlsalt = salt['grains.get']('samlsalt', 'mc5tbaeuwn8mpxfx07sxq2wv2vi4utsw') %}

/var/ilias/data/ilias/auth/saml/config/authsources.php:
  file.managed:
    - source: salt://saml/authsources.php.j2
    - template: jinja
    - context:
      ilias_http_path: {{ doil_domain }}
    - user: root
    - group: root
    - mode: 644

/var/ilias/data/ilias/auth/saml/config/config.php:
  file.managed:
    - source: salt://saml/config.php.j2
    - template: jinja
    - context:
      ilias_http_path: {{ doil_domain }}
      samlsecretsalt: {{ samlsalt }}
      mpass: {{ mpass }}
      samlpass: {{ samlpass }}
    - user: root
    - group: root
    - mode: 644

/var/ilias/cert:
  file.directory:
    - user: www-data
    - group: www-data
    - dir_mode: 755

install_certs:
  cmd.run:
    - name: openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out saml.crt -keyout saml.pem -batch
    - cwd: /var/ilias/cert
    - require:
        - file: /var/ilias/cert

/var/www/html/SetIdp.php:
  file.managed:
    - source: salt://saml/SetIdp.php.j2
    - template: jinja
    - context:
      ilias_http_path: {{ doil_domain }}
      idp_meta: %TPL_IDP_META%
      keycloak_host_name: %TPL_KEYCLOAK_HOSTNAME%
    - user: root
    - group: root
    - mode: 644

init_ilias_idp:
  cmd.run:
    - name: php SetIdp.php
    - cwd: /var/www/html
    - require:
        - file: /var/www/html/SetIdp.php

/root/addInstanceToKeycloak.sh:
  file.managed:
    - source: salt://saml/addInstanceToKeycloak.sh.j2
    - template: jinja
    - context:
      server_host_name: %TPL_KEYCLOAK_HOSTNAME%
      doil_domain: {{ doil_domain }}
    - user: root
    - group: root
    - mode: 744

addInstanceToKeycloak:
  cmd.run:
    - name: ./addInstanceToKeycloak.sh
    - cwd: /root
    - runas: root
    - require:
        - file: /root/addInstanceToKeycloak.sh

    urn:oasis:names:tc:SAML:2.0:attrname-format:basic
{% set maj = salt['cmd.shell']('php -r "echo PHP_MAJOR_VERSION;"')  %}
{% set min = salt['cmd.shell']('php -r "echo PHP_MINOR_VERSION;"')  %}
{% set instance_name = salt['grains.get']('doil_project_name', '') %}

php{{ maj }}.{{ min }}-xdebug:
  pkg.installed

/etc/php/{{ maj }}.{{ min }}/apache2/php.ini:
  file.append:
    - text:
      - ; XDEBUG-START
      - xdebug.mode = develop,debug,profile
      - xdebug.discover_client_host = true
      - xdebug.client_port = 9000
      - xdebug.log = /var/log/doil/xdebug/xdebug_{{ instance_name }}.log
      - xdebug.start_with_request = trigger
      - xdebug.output_dir = /var/log/doil/xdebug/xprofiles_{{ instance_name }}
      - ; XDEBUG-END

apache2_supervisor_signal:
  cmd.run:
    - name: supervisorctl restart apache2
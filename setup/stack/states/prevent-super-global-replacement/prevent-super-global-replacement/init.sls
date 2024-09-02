{% set ilias_version = salt['grains.get']('ilias_version', '9') %}

{% if ilias_version | int >= 8 %}
/var/www/html/data/ilias/client.ini.php:
  file.blockreplace:
    - marker_start: '[server]'
    - marker_end: '[client]'
    - content: "start = \"./login.php\"\nprevent_super_global_replacement = \"1\"\n\n"
{% endif %}
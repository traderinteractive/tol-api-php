FROM nubs/phpunit

USER root

RUN pacman-key --populate archlinux && pacman-key --refresh-keys && pacman --sync --refresh --noconfirm --noprogressbar --quiet && pacman --sync --refresh --sysupgrade --noconfirm --noprogressbar --quiet && pacman-db-upgrade && pacman --sync --noconfirm --noprogressbar --quiet php-mongo

USER build

ADD provisioning/set-env.sh /home/build/set-env.sh

ENTRYPOINT ["/home/build/set-env.sh"]
CMD ["/code/build.php"]

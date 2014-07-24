FROM nubs/phpunit

USER root

RUN pacman --sync --refresh --noconfirm --noprogressbar --quiet
RUN pacman --sync --noconfirm --noprogressbar --quiet php-mongo

USER build

ADD provisioning/set-env.sh /home/build/set-env.sh

ENTRYPOINT ["/home/build/set-env.sh"]
CMD ["/code/build.php"]

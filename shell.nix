{ pkgs ? import <nixpkgs> {} }:
pkgs.mkShell {
    nativeBuildInputs = with pkgs.buildPackages;
    let
        php82 = pkgs.php82.buildEnv {
            extensions = ({ enabled, all }: enabled ++ (with all; [
                ctype
                iconv
                intl
                mbstring
                pdo
                redis
                xdebug
                xsl
            ]));
            extraConfig = ''
               memory_limit=8G
               post_max_size=200M
               upload_max_filesize=200M
               date.timezone=Europe/Prague
               phar.readonly=Off
               xdebug.mode=debug
            '';
        };
     in
     [
        php82
        php82.packages.composer
        php82.extensions.redis
        php82.extensions.xsl
        php82.extensions.mbstring
        symfony-cli
        git
        nodejs_18
        nodePackages.serverless
    ];
}

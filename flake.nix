{
  description = "Laravel development environment with PHP 8.3 and Node LTS";
  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
  };
  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        php = pkgs.php83.buildEnv {
          extensions = ({ enabled, all }: enabled ++ (with all; [
            # Required Laravel extensions
            bcmath
            curl
            dom
            fileinfo
            mbstring
            pdo
            pdo_mysql
            tokenizer
            xml
            xmlwriter
            zip
            # Additional useful extensions
            gd
            intl
            opcache
            redis
            sodium
          ]));
          extraConfig = ''
            memory_limit = 256M
            upload_max_filesize = 256M
            post_max_size = 256M
            max_execution_time = 180
            date.timezone = UTC
          '';
        };
      in
      {
        devShells.default = pkgs.mkShell {
          packages = with pkgs; [
            # PHP and related tools
            php
            php.packages.composer
            # Node LTS and related tools
            nodejs_20
            nodePackages.npm
            # Development tools
            phpPackages.php-cs-fixer
            nodePackages.yarn
          ];

          shellHook = ''
            # Preserve the current shell
            export SHELL=${toString (builtins.getEnv "SHELL")}

            printf "\033[0;32m🚀 Laravel development environment loaded!\033[0m\n"
            printf "\033[0;34m📦 PHP version: $(php -v | head -n 1)\033[0m\n"
            printf "\033[0;34m📦 Node version: $(node -v)\033[0m\n"
            printf "\033[0;34m📦 Composer version: $(composer -V)\033[0m\n"
          '';
        };
      }
    );
}

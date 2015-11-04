#!/bin/sh
set -e

ROOT_PATH="$( cd "$( dirname "$0" )" && pwd )"

env="dev"
force=0
interactive=1
args=""
rabbit_host="127.0.0.1"
rabbit_port="15672"

while [ $# -gt 0 ]; do
    key="$1"

    case $key in
        --environment|--env)
            shift
            args="$args --env $1"
        ;;

        --user|-u)
            shift
            args="$args --user $1"
        ;;

        --password|-p)
            stty -echo
            read -p "Enter password : " password; echo
            stty echo

            args="$args --password $password"
        ;;

        --host|-h)
            shift
            rabbit_host=$1
        ;;

        --port|-p)
            shift
            rabbit_port=$1
        ;;

        --force|-f)
            force=1
        ;;

        --no-interaction)
            interactive=0
        ;;

        -h|--help)
            echo "Usage : $ROOT_PATH/bin/rabbit.sh [-h|--help|--env[ironment] <value>|--user <user>|-u <user>|-f|--force|--no-interaction|--password|-p]|--host <host>|-h <host>|--port <port>|-p <port>"
            exit 0
        ;;

        *)
            echo "Unexpected argument \"$1\""
            exit 2
        ;;
    esac

    shift
done

BINARY_PATHS="$ROOT_PATH/app/cache/$env/amqp"

if ! type "rabbitmqadmin" > /dev/null; then
    noRabbitMqAdmin=1
else
    noRabbitMqAdmin=0
fi

if [ $force = 1 -o $noRabbitMqAdmin = 1 ]; then
    if [ $interactive = 1 ]; then
        read -p "It seems you do not have an accessible rabbitmqadmin. Activate it ? " confirm
    else
        confirm="Y"
    fi

    case $confirm in
        [Yy]*)
            if [ $rabbit_host = "127.0.0.1" ] || [ $rabbit_host = "::1" ] || [ $rabbit_host = "fe80::1" ]; then
                echo "# Enable rabbitmq_management plugin"
                rabbitmq-plugins enable rabbitmq_management
            fi

            echo
            echo "# Installing rabbitmqadmin"
            curl -X GET http://${rabbit_host}:${rabbit_port}/cli/rabbitmqadmin > /usr/local/bin/rabbitmqadmin
            chmod +x /usr/local/bin/rabbitmqadmin
        ;;

        [Nn]*) ;;

        *)
            echo "Please answer yes or no"
            exit 2
        ;;
    esac
fi

echo $args

echo
echo "#Generating config files..."
php "$ROOT_PATH/../app/console" wisembly:amqp:config-handler -vv $args

for f in $(ls "$BINARY_PATH" | grep "sh"); do
    echo
    "$BINARY_PATH/$f"
done


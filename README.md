Amqp Swarrot Bundle
===================
This bundle integrates Swarrot into Symfony, with another approach from the
[SwarrotBundle](http://github.com/swarrot/SwarrotBundle). Currently in early
stages of development... and opensourcing.

But, unlike the SwarrotBundle, this bundle does not allow you (...yet ?) to
configure multiple consumers, and force you to use the `CommandProcessor`
approach (Every message should be treated in the `CommandProcessor`, which will
be treated through a Symfony `Process`).

Installation
------------
The recommended way is to go through Composer. Once you have installed it, you
should run the require command: `composer require wisembly\amqp-bundle`, and
pick the latest version available on packagist (you should avoid `@stable`
meta-constraint). Note that a flex recipe is available. :}

Configuration Reference
-----------------------
The configuration reference can be found through the command
`app/console config:dump-reference WisemblyAmqpBundle` :

```yaml
# Default configuration for extension with alias: "wisembly_amqp"
wisembly_amqp:

    # Default connection to use
    default_connection:   null

    # Broker to use
    broker:               ~ # Required

    # Path to sf console binary
    console_path:         ~ # Required

    # Logger channel to use when a logger is required
    logger_channel:       amqp

    # Connections to AMQP to use
    connections:          # Required

        # Prototype
        name:
            uri:                  null
            host:                 null
            port:                 null
            login:                null
            password:             null
            vhost:                null
            query:                null

    # Access gate for each dialog with AMQP
    gates:

        # Prototype
        name:

            # Does the queue and the exchange be declared before use them
            auto_declare:         true

            # Connection to use with this gate
            connection:           null

            # Routing key to use when sending messages through this gate
            routing_key:          null

            # Queue to fetch the information from
            queue:                # Required
                name:                 ~ # Required
                options:
                    passive:              false
                    durable:              true
                    exclusive:            false
                    auto_delete:          false
                    arguments:

                        # Prototype
                        name:                 ~

            # Exchange point associated to this gate
            exchange:             # Required
                name:                 ~ # Required
                options:
                    type:                 null
                    passive:              false
                    durable:              true
                    auto_delete:          false
                    internal:             false
                    arguments:

                        # Prototype
                        name:                 ~
```

Usage
-----
### Concept of "Gates"
This concept is the whole difference with the Swarrot Bundle : Gates. It is a
simple value object containing information on the queue / exchange to use for
your actions (which connection to use, which queue it should target, which
exchange should be used, which routing key, ... and configuration for these,
such as should the exchange / queue be declared if not existent, and so on).

Refer to the configuration reference for more information on what is possible
to configure for a "gate". Once you have at least one, you can consume from one,
or publish to one. For that, see below.


### Publishing a Message
You may publish a new message to a gate (access point). For that, you should
retrieve the services `Wisembly\AmqpBundle\GatesBag` to fetch the right gate,
and then use the `Wisembly\AmqpBundle\Publisher` service to publish a message
that can be understood by the `CommandProcessor` :

```php
use Wisembly\AmqpBundle\Message;
use Wisembly\AmqpBundle\Publisher;

$message = new Message(
    'symfony:command:to:run',
    [
        'list',
        'of',
        'arguments'
        'and'
        'options'
    ]
]));

// Using swarrot's Swarrot\Broker\Message is also possible if you don't plan on
// consuming the message through the provider consumer

$publisher->publish($message, 'my_gate');
// or $publisher->publish($message, $gate); with `$gate` an instanceof Gate
```

Your message is then ready to be consumed by the consumer. But, if you don't
want to use the AmqpBundle's consumer, you're not forced to use the same syntax
and use whatever you need !

### Consuming a Message
In order to consume the messages that are expected by this bundle, all you have
to do is run the following command :

```
php app/console wisembly:amqp:consume gate
```

A bunch of options are available (such as activating a RPC mechanism, defining
a polling interval, ...), but you can check all of those in the `--help` option.

Whenever a message is consumed by this consumer, it will launch a new `Process`
to treat it, and will pass the environment and verbosity to the command that is
run. The output will then be retrieved and printed on the consumer's output.

### Implementing a Broker
Two brokers are built-in :

- The `Wisembly\AmqpBundle\Broker\PeclBroker` ([php amqp extension](https://pecl.php.net/package/amqp))
  Note that if the pecl extension is not loaded / installed, the broker won't
  be available.
- [`Wisembly\AmqpBundle\Broker\PhpAmqpLibBroker`](https://github.com/php-amqplib/php-amqplib),
  which is the one implemented in full PHP

The recommended broker is to use if available the
`Wisembly\AmqpBundle\Broker\PeclBroker` broker.

But you can implement more of those (such as a Redis one or whatever else !) by
implementing the `Wisembly\AmqpBundle\BrokerInterface` interface. If using the
`autoconfigure` setting of the dic 3.4+, that's all you have to do. If you want
to add an alias or if not using the `autoconfigure` feature, you can add a
`wisembly.amqp.broker` tag, which can have an alias (it will take the service's
name if no alias are specified).

Credits
-------
Developed with love at the Wisembly Factory, based on the
[SwarrotBundle](http://github.com/swarrot/SwarrotBundle).

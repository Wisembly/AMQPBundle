Amqp Swarrot Bundle
===================
This bundle integrates Swarrot into Symfony2, with another approach from the
[SwarrotBundle](http://github.com/swarrot/SwarrotBundle). Currently in early
stages of development... and opensourcing.

But, unlike the SwarrotBundle, this bundle does not allow you (yet ?) to
configure multiple consumers, and force you to use the `CommandProcessor`
approach (Every message should be treated in the `CommandProcessor`, which will
be treated through a Symfony `Process`.

Installation
------------
The recommended way is to go through Composer. Once you have installed it, you
should run the require command: `composer require wisembly\amqp-bundle`, and
pick the latest version available on packagist (you should avoid `@stable`
meta-constraint).

Once it is installed, you can add the bundle to your `AppKernel` file :

```php
public function registerBundles()
{
    $bundles = [
        // ...
        new Wisembly\AmqpBundle\WisemblyAmqpBundle
    ];

    // ...

    return $bundles;
}
```

Configuration Reference
-----------------------
The configuration reference can be found through the command
`app/console config:dump-reference WisemblyAmqpBundle` :

```yaml
wisembly_amqp:

    # AMQP Configuration
    amqp:

        # Default connection to use
        default_connection:   null

        # Broker to use
        broker:               ~ # Required

        # Connections to AMQP to use
        connections:          # Required

            # Prototype
            name:
                host:                 ~ # Required
                port:                 ~ # Required
                login:                ~ # Required
                password:             ~ # Required
                vhost:                /

        # Access gate for each dialog with AMQP
        gates:

            # Prototype
            name:

                # Connection to use with this gate
                connection:           null

                # Exchange point associated to this gate
                exchange:             ~ # Required

                # Routing key to use when sending messages through this gate
                routing_key:          null

                # Queue to fetch the information from
                queue:                ~ # Required
```

Usage
-----
### Publishing a Message
You may publish a new message to a gate (access point). For that, you should
retrieve the services `wisembly.amqp.gates` to fetch the right gate, and then
use the `wisembly.amqp.publisher` service to publish a message that can be
understood by the `CommandProcessor` :

```php
use Swarrot\Broker\Message;

$gate = $this->get('wisembly.amqp.gates.my_gate');

// or you can fetch the bag of gates :
// $gates = $this->get('wisembly.amqp.gates');
// $gate = $gates->get('my_gate');

$publisher = $this->get('wisembly.amqp.publisher);

$message = new Message(json_encode([
    'command' => 'symfony:command:to:run',
    'arguments' => [
        'list',
        'of',
        'arguments'
        'and'
        'options'
    ]
]));

$publisher->publish($message, $gate);
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
runned. The output will then be retrieved and printed on the consumer's output.

### Implementing a Broker
Two brokers are built-in : 

- the `pecl` ([php amqp extension](https://pecl.php.net/package/amqp)) ;
- [`oldsound`](https://github.com/videlalvaro/php-amqplib), which is the one
  implemented in full PHP by @videlalvaro

The recommended broker is to use if available the `pecl` broker.

But you can implement more of those (such as a Redis one or whatever else !) by
implementing the `Wisembly\AmqpBundle\BrokerInterface` interface, and adding a
`wisembly.amqp.broker` tag, which can have an alias (it will take the service's
name if no alias are specified).

Credits
-------
Developed with love at the Wisembly Factory, based on the
[SwarrotBundle](http://github.com/swarrot/SwarrotBundle).

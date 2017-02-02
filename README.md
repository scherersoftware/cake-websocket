![CakePHP 3 Websocket  Plugin](https://raw.githubusercontent.com/scherersoftware/cake-websocket/master/websocket.png)

[![Build Status](https://travis-ci.org/scherersoftware/cake-websocket.svg?branch=master)](https://travis-ci.org/scherersoftware/cake-websocket)
[![License](https://poser.pugx.org/scherersoftware/cake-websocket/license)](https://packagist.org/packages/scherersoftware/cake-websocket)
[![Latest Stable Version](https://poser.pugx.org/scherersoftware/cake-websocket/v/stable)](https://packagist.org/packages/scherersoftware/cake-websocket)
[![Latest Unstable Version](https://poser.pugx.org/scherersoftware/cake-websocket/v/unstable)](https://packagist.org/packages/scherersoftware/cake-websocket)
[![Monthly Downloads](https://poser.pugx.org/scherersoftware/cake-websocket/d/monthly)](https://packagist.org/packages/scherersoftware/cake-websocket)

## Introduction

This CakePHP 3 plugin gives you an easy way to add websocket capability to your web application.

#### Main Packages

- [Cake Frontend Bridge](https://github.com/scherersoftware/cake-frontend-bridge)
- [Ratchet](https://github.com/ratchetphp/Ratchet)
- [CakePHP Queuesadilla](https://github.com/josegonzalez/cakephp-queuesadilla)

#### Requirements

- CakePHP Database Sessions
- PHP 7.1

---

## Usage in 4 easy steps

**Note:** You can checkout our [CakePHP App Template](https://github.com/scherersoftware/cake-app-template) for testing it on a clean app setup with preinstalled dependencies.

#### 1. Define a new event

**Example for `websocket_events.php`**

```
...
'userDataUpdated' => [
    'audience' => [
        'includeAllNotAuthenticated' => false,
        'includeAllAuthenticated' => true
    ]
]
...
```

#### 2. Publish the event in server context (e.g. Shell, Controller, Table...)

**Example for `UsersController.php`**

```
...
use Websocket\Lib\Websocket;
...
if ($this->Users->save($exampleUser)) {
    Websocket::publishEvent('userDataUpdated', ['editedUserId' => $exampleUser->id]);
}
...
```

#### 3. Let the client receive the event and define a callback

**Example for `../users/index_controller.js`**

```
...
App.Websocket.onEvent('userDataUpdated', function(payload) {
    if (payload.editedUserId === this.exampleUser.id) {
        alert('Someone changed the data of this user!');
    }
}.bind(this));
...
```

#### 4. Run the websocket server shell and start testing!

```
$ bin/cake websocket_server
```

---

## Installation

#### 1. Require the plugin

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require scherersoftware/cake-websocket
```

#### 2. Load the plugin

The next step is to load the plugin properly inside your bootstrap.php:

```
Plugin::load('Websocket', ['bootstrap' => true, 'routes' => true]);
```

#### 3. Configure app config

- **File:** `/config/app.php`

    ```
    <?php
    ...
    'Websocket' => [
        'ssl' => false,
        'host' => 'cws.dev',
        'port' => 8889,
        'frontendPath' => [
            'normal' => '/',
            'ssl' => '/'
        ],
        'sessionCookieName' => 'cws',
        'Queue' => [
            'name' => 'websocket',
            'loopInterval' => 0.1,
        ]
    ]
    ...
    ```

#### 4. Create and configure websocket events

- **File:** `/config/websocket_events.php`

    ```
    <?php
    return [
        'userDataUpdated' => [
            'audience' => [
                'includeAllNotAuthenticated' => false,
                'includeAllAuthenticated' => true
            ]
        ]
    ];
    ```

#### 5. Configure `AppController.php`

In your `src/Controller/AppController.php`, insert the following pieces of code


**Usage:**

```
use Websocket\Lib\Websocket;
```

**beforeFilter():**

```
...
$this->FrontendBridge->setJson('websocketFrontendConfig', Websocket::getFrontendConfig());
...
```


#### 6. Make the JS websocket lib globally accessible under `App.Websocket`

- Load the file /webroot/lib/websocket.js after loading the Frontend Bridge assets

#### 7. Setup sessions properly if not alread done

Please follow the [Cake Database Sessions Documentation](https://book.cakephp.org/3.0/en/development/sessions.html#database-sessions)

---

## Roadmap

#### 1.1.0
- Unit Tests
- Add a websocket_connections table which stores all active connections
- Support of cake and cache sessions

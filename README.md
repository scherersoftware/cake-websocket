![CakePHP 3 Websocket  Plugin](https://raw.githubusercontent.com/scherersoftware/cake-websocket/master/websocket.png)

[![Build Status](https://travis-ci.org/scherersoftware/cake-websocket.svg?branch=master)](https://travis-ci.org/scherersoftware/cake-websocket)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)


## Usage in 4 easy steps

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
    Websocket::publishEvent('userDataUpdated', ['editedUserId' => $exampleUser->id], []);
}
...
```

#### 3. Let the client receive the event and define a callback

**Example for `../users/index_controller.js`**

```
...
App.Main.Websocket.onEvent('userDataUpdated', function(payload) {
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

- `app.php`

	**path:**`/config/`

    **example**

    ```
    <?php
    ...
    'Websocket' => [
        'ssl' => false,
        'host' => 'cws.dev',
        'port' => 8889,
        'sessionCookieName' => 'cws',
        'Queue' => [
            'name' => 'websocket',
            'loopInterval' => 0.1,
        ]
    ]
    ...
    ```

#### 4. Create and configure websocket events

- `websocket_events.php`

	**path:**`/config/`

    **example**

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
$this->FrontendBridge-setJson('websocketFrontendConfig', Websocket::getFrontendConfig());
...
```


#### 6. Make the JS websocket component globally accessible under `App.Main.Websocket`

- `app_controller.js`

	**path:**`/webroot/js/app/`

    **component property**

    ```
    ...
    components: ['Websocket'],
    ...
    ```


	**_initialize() code:**


	```
    ...
    if (!this.getVar('isAjax')) {
        this.Websocket.setup();
        App.Main.Websocket = this.Websocket;
    }
    ...
    ```

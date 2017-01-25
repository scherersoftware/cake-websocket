<?php
namespace Websocket\Controller;

use App\Controller\AppController;
use Cake\Network\Response;
use Websocket\Lib\Websocket;

class ExampleController extends AppController
{

    /**
     * index function
     *
     * @return void
     */
    public function index(): void
    {
        $this->loadModel('Users');

        $exampleUser = $this->Users->find()
            ->order([
                'created' => 'ASC'
            ])
            ->first();

        $this->set('exampleUser', $exampleUser);
    }

    /**
     * action to load data panel of user
     *
     * @return \Cake\Network\Response
     */
    public function userDataPanel(): Response
    {
        $this->loadModel('Users');

        $exampleUser = $this->Users->find()
            ->order([
                'created' => 'ASC'
            ])
            ->first();

        $this->FrontendBridge->setBoth('exampleUser', $exampleUser);

        return $this->render('/Element/user_data_panel');
    }

    /**
     * action to load form panel of user
     *
     * @return \Cake\Network\Response
     */
    public function userFormPanel(): Response
    {
        $this->loadModel('Users');

        $exampleUser = $this->Users->find()
            ->order([
                'created' => 'ASC'
            ])
            ->first();

        if ($this->request->is(['post', 'patch', 'put'])) {
            $exampleUser->accessible('*', false);
            $exampleUser->accessible(['firstname', 'lastname'], true);
            $this->Users->patchEntity($exampleUser, $this->request->data, [
                'validate' => false
            ]);

            if ($this->Users->save($exampleUser)) {
                Websocket::publishEvent('userDataUpdated', ['editedUserId' => $exampleUser->id], []);
            }
        }

        $this->FrontendBridge->setBoth('exampleUser', $exampleUser);

        return $this->render('/Element/user_form_panel');
    }
}

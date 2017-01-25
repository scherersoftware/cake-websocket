<div class="users form-panel box">
    <div class="box-header with-border">
        <h3 class="box-title">
            User Form
        </h3>
    </div>
    <div class="box-body">
        <?= $this->Form->create($exampleUser) ?>
        <?= $this->Form->input('firstname', [
            'value' => ''
        ]) ?>
        <?= $this->Form->input('lastname', [
            'value' => ''
        ]) ?>
        <?= $this->Form->submit() ?>
        <?= $this->Form->end() ?>
    </div>
</div>

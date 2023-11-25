<?php
class NewFormModel extends CFormModel {
    public function behaviors() {
        return [
            'newBeforeValidateBehavior' => [
                'class' => 'NewBeforeValidateBehavior',
            ],
        ];
    }
}
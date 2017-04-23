<?php
require_once __DIR__ . "/_top.php";

$translate = new \MM\Util\Translate(array(
    'translation' => array(
        'en' => array(
            '__form_checkbox_not_checked' => "This is translated checbox message",
            '__form_input_required' => "Value is required custom message",
            'first_name_label' => 'Translated First Name',
            'OUKEJ' => 'Translated Submit Value',
        ),
    ),
));

// prx(class_exists('\MM\Util\Translate'));

$form = new \MM\Form\Form(array(
    'attributes' => array(
        'method' => 'get',
        'action' => '',
        'style' => 'padding: 20px; margin: 20px; background: #eee;',
        //'class' => 'form-inline',
    ),
    'translate' => $translate,
    'elements' => array(
        new \MM\Form\Element\Hidden('hideme', array(
            'value' => '123',
            'validators' => array(
                new \MM\Form\Validator\Callback(function($value, $context){
                    if ($value != 123) {
                        return "Invalid hidden value";
                    }
                    return true;
                })
            ),
        )),
        new \MM\Form\Element\Text('first', array(
            'label' => 'first_name_label',
        )),
        new \MM\Form\Element\Text('last', array(
            'label' => 'Last Name',
            'required' => 1,
            'attributes' => array(
                'onclick' => 'console.log("jaj")',
            ),
        )),
        new \MM\Form\Element\Text('email', array(
            'label' => 'Email',
            'validators' => array(
                new \MM\Form\Validator\Email,
            ),
        )),
        new \MM\Form\Element\Password('password', array(
            'label' => 'Password',
            'required' => 1,
            'validators' => array(
                new \MM\Form\Validator\StringLength(6)
            ),
        )),
        new \MM\Form\Element\Password('password_check', array(
            'label' => 'Password Confirm',
            'required' => 1,
            'validators' => array(
                new \MM\Form\Validator\Callback(function($value, $context){
                    // context je parent form
                    if ($value != $context->password->getValue()) {
                        return "Passwords don't match";
                    }
                    return true;
                })
            ),
        )),

        new \MM\Form\Element\Select("selector", array(
            'label' => 'Selektik',
            'multiOptions' => array(
                '' => '', 'a' => null, 'b' => null, 'c' => 'céčko',
                'some label' => array(
                    'd' => null,
                    'e' => 'éééé'
                )
            ),
            'required' => 1,
        )),

        new \MM\Form\Element\Textarea('longtext', array(
            'label' => 'Rozpis sa',
            'value' => 'foo',
            'required' => 1,
        )),

        new \MM\Form\Element\Checkbox('checkmate', array(
            'label' => 'No tak sa ukaz',
            'required' => 1,
        )),

        new \MM\Form\Element\Checkbox('render_errors', array(
            'label' => 'Render element errors?',
            'value' => 1,
        )),

        // radiogroup
        new \MM\Form\Element\Radio("radioamater", array(
            'label' => 'Vyber si radio', // ignored
            'multiOptions' => array(
                'a' => null, 'b' => 'label pre b', 'c' => 'céčko',
            ),
            'required' => 1,
            //'value' => 'b', // initaly checked
        )),

        new \MM\Form\Element\Pseudo('comment', array(
            'value' => 'Toto je hociake <b>html</b> ho ho',
        )),

        new \MM\Form\Element\Button('submit_button', array(
            'value' => 'submit button',
            'attributes' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary',
            )
        )),

        new \MM\Form\Element\Image('imgsubmit', array(
            'attributes' => array(
                'src' => 'submit.png',
                'alt' => 'OK',
                'style' => 'width:auto;',
            )
        )),
    ),
));

// vsetky elementom pausalne dame trim
$trim = new \MM\Form\Filter\Trim;
$utf8 = new \MM\Form\Validator\Utf8;
foreach ($form as $e) {
    /** @var \MM\Form\Element $e */
    $e->addFilter($trim);
    $e->addValidator($utf8);
}

// ukazka setupnutie templatov, anpr:
// \MM\Form\Element::setGlobalTemplates(array(
//     'errors' => '<p>{{error}}</p>',
//     'error' => '{{message}} ',
// ));


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>MM Form Demo</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

<style type="text/css">
form.ok label {
    color: green;
}
form .required > label {
    font-weight: bold;
}
form .required > label:after {
    content:" *";
}
form ul.mmfe-errors {
    color: red;
}
form .error > input, form .error > textarea, form .error > select  {
    border-color: red;
}
form .error > label {
    color: red;
}
</style>

    </head>
    <body>
<?php
    if (!empty($_GET)) {

        $form->setData($_GET);
        printf('<pre>$_GET: %sForm data: %s</pre>',
            print_r($_GET, true), print_r($form->getData(), true)
        );
        if ($form->isValid()) {
            $form->setAttribute("class", 'ok');
        }
    }
    printf("<p><a href='%s'>reset</a></p>", basename($_SERVER['SCRIPT_NAME']));
    echo $form->render((bool) $form->render_errors->getValue());
?>
    </body>
</html>
<?php

test('the application redirects home to admin panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin');
});

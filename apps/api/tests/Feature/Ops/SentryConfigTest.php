<?php

it('boots with an empty sentry dsn', function () {
    expect(config('sentry.dsn'))->toBeIn([null, '']);
});

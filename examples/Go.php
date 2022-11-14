<?php
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine\Channel;

$users = [
    [
        'id' => 1,
        'name' => 'John Doe',
        'age' => 42,
    ], [
        'id' => 2,
        'name' => 'Chris Pen',
        'age' => 44,
    ]
];

$hobbies = [
    [
        'userId' => 1,
        'hobby' => 'Stamps',
    ], [
        'userId' => 1,
        'hobby' => 'golf',
    ], [
        'userId' => 2,
        'hobby' => 'fishing',
    ]
];

$chan = new Channel(1);
$chan2 = new Channel(2);

Co\run(function () use ($users, $hobbies, $chan, $chan2) {
    $wg = new WaitGroup();

    go(function () use ($wg, $users, &$dbUsers, $chan) {
        $wg->add();
        $chan->push('collecting user data');
        co::sleep(3);
        $dbUsers = $users;
        $chan->push('user data collected');
        $wg->done();
    });

    $dbHobbies = [];
    go(function () use ($wg, $hobbies, &$dbHobbies, $chan) {
        $wg->add();
        $chan->push('collecting hobby data');
        $dbHobbies = $hobbies;
        $chan->push('hobby data collected');
        $wg->done();
    });

    go('channelOut', $chan);

    $wg->wait();
    $chan->close();

    $wg2 = new WaitGroup();
    go(function () use ($wg2, $dbHobbies, $dbUsers, $chan2) {
        $wg2->add();
        $count = \count($dbUsers);

        foreach ($dbUsers as $key => $user) {
            co::sleep(random_int(1, 5));
            $save[$user['id']] = $dbUsers + ['hobbies' => []];

            foreach ($dbHobbies as $hobby) {
                if ($user['id'] === $hobby['userId']) {
                    $save[$user['id']]['hobbies'][] = $hobby['hobby'];
                }
            }

            $chan2->push(($key + 1) . ' of ' . $count . ' users saved');
            co::sleep(1);
        }

        $chan2->push($save);

        $wg2->done();
    });

    go('channelOut', $chan2);

    $wg2->wait();
    $chan2->close();
});


function channelOut($x)
{
    $stats = count($x->stats()) + 1 ?? 0;

    while ($stats) {
        $data = $x->pop();

        if (\is_array($data) === true) {
            var_dump($data);
        } else {
            echo $data . "\n";
        }

        --$stats;
    }
}
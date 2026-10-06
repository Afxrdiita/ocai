<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>10 Fun Facts</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 640px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #333; }
        ol li { margin: 10px 0; line-height: 1.5; }
    </style>
</head>
<body>
    <h1>10 Fun Facts</h1>
    <ol>
        <?php
        $facts = [
            "Honey never spoils — edible pots found in ancient tombs are still good.",
            "Octopuses have three hearts and blue blood.",
            "A day on Venus is longer than a year on Venus.",
            "Bananas are berries, but strawberries are not.",
            "There are more possible chess games than atoms in the observable universe.",
            "Wombat poop is cube-shaped.",
            "The first computer bug was an actual moth, found in 1947.",
            "Sharks existed before trees did.",
            "A group of flamingos is called a flamboyance.",
            "Hot water can freeze faster than cold water — the Mpemba effect.",
        ];
        foreach ($facts as $fact) {
            echo "<li>" . htmlspecialchars($fact) . "</li>";
        }
        ?>
    </ol>
</body>
</html>

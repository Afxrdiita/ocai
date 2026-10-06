<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fun Facts</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #0a0a12;
            font-family: Arial, sans-serif;
        }

        .title-box {
            border: 4px solid #00f0ff;
            border-radius: 18px;
            padding: 30px 70px;
            margin-bottom: 50px;
            background: rgba(0, 240, 255, 0.05);
            box-shadow:
                0 0 10px #00f0ff,
                0 0 30px #00f0ff,
                0 0 60px rgba(0, 240, 255, 0.5),
                inset 0 0 15px rgba(0, 240, 255, 0.3);
        }

        .title-box h1 {
            margin: 0;
            font-size: 64px;
            font-weight: 900;
            letter-spacing: 6px;
            color: #fff;
            text-shadow:
                0 0 10px #00f0ff,
                0 0 30px #00f0ff,
                0 0 60px #00f0ff;
            text-transform: uppercase;
        }

        .fact-row {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .fact-box {
            border: 2px solid #ff00e6;
            border-radius: 12px;
            padding: 20px 30px;
            width: 420px;
            min-height: 90px;
            display: flex;
            align-items: center;
            background: rgba(255, 0, 230, 0.05);
            box-shadow:
                0 0 10px #ff00e6,
                0 0 30px rgba(255, 0, 230, 0.6);
            transition: opacity 0.2s;
        }

        .fact-box span {
            color: #eee;
            font-size: 18px;
            line-height: 1.5;
        }

        .lever {
            width: 70px;
            height: 240px;
            background: linear-gradient(#222, #111);
            border: 3px solid #444;
            border-radius: 35px;
            position: relative;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.8);
        }

        .lever::after {
            content: "PULL";
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            color: #888;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .lever-stick {
            position: absolute;
            left: 50%;
            bottom: 20px;
            width: 10px;
            height: 140px;
            background: linear-gradient(90deg, #555, #999, #555);
            transform: translateX(-50%);
            transform-origin: bottom center;
            transition: transform 0.15s ease-in;
        }

        .lever-knob {
            position: absolute;
            top: -25px;
            left: 50%;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #ff5a5a, #b00000 70%);
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.7);
            transform: translateX(-50%);
        }

        .lever.pulled .lever-stick {
            transform: translateX(-50%) rotate(180deg);
            transition: transform 0.15s ease-out;
        }

        .lever-base {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 46px;
            height: 30px;
            background: linear-gradient(#333, #000);
            border-radius: 6px;
        }

        .test-link {
            margin-top: 50px;
            padding: 14px 40px;
            border: 2px solid #00f0ff;
            border-radius: 10px;
            background: rgba(0, 240, 255, 0.05);
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            text-decoration: none;
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.5);
            transition: box-shadow 0.2s;
        }

        .test-link:hover {
            box-shadow: 0 0 20px #00f0ff;
        }
    </style>
</head>
<body>
    <div class="title-box">
        <h1>Fun Facts</h1>
    </div>

    <div class="fact-row">
        <div class="fact-box">
            <span id="fact-text">Pull the lever to get a fun fact!</span>
        </div>

        <div class="lever" id="lever" title="Pull the lever!">
            <div class="lever-stick">
                <div class="lever-knob"></div>
            </div>
            <div class="lever-base"></div>
        </div>
    </div>

    <a class="test-link" href="quiz.php">Take the test!</a>

    <script>
        const facts = <?php
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
                "A bolt of lightning is five times hotter than the surface of the sun.",
                "Sea otters hold hands while sleeping so they don't drift apart.",
                "The Eiffel Tower can grow more than 15 cm taller in summer heat.",
                "There is enough DNA in your body, stretched end to end, to reach Pluto and back.",
                "Cows have best friends and get stressed when separated from them.",
                "A single strand of spider silk is stronger than steel of the same thickness.",
                "The shortest war in history lasted 38 minutes — Britain vs Zanzibar, 1896.",
                "Sloths can hold their breath longer than dolphins can.",
                "Scotland's national animal is the unicorn.",
                "Human teeth are as hard as shark teeth.",
                "Bubble wrap was originally invented as textured wallpaper.",
                "Ants take 250 naps a day, each lasting about a minute.",
                "The dot over the letter 'i' is called a tittle.",
                "Avocados are toxic to birds.",
                "You cannot hum while holding your nose closed.",
                "The loudest animal on Earth is the pistol shrimp — it stuns prey with a sonic blast.",
                "Venus is the only planet that spins clockwise.",
                "A 'jiffy' is an actual unit of time — 1/100th of a second.",
                "Cats have over 100 vocal sounds; dogs have about 10.",
                "A teaspoonful of neutron star matter would weigh about 6 billion tons.",
            ];
            echo json_encode($facts);
        ?>;

        const lever = document.getElementById("lever");
        const factText = document.getElementById("fact-text");
        let current = -1;
        let pulling = false;

        lever.addEventListener("click", () => {
            if (pulling) return;
            pulling = true;
            lever.classList.add("pulled");

            setTimeout(() => {
                let next;
                do {
                    next = Math.floor(Math.random() * facts.length);
                } while (facts.length > 1 && next === current);
                current = next;

                factText.style.opacity = 0;
                setTimeout(() => {
                    factText.textContent = facts[current];
                    factText.style.opacity = 1;
                }, 100);

                lever.classList.remove("pulled");
                pulling = false;
            }, 300);
        });
    </script>
</body>
</html>

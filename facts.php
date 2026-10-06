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
                "Bananas are radioactive — they contain potassium-40.",
                "The Great Wall of China is NOT visible from the Moon with the naked eye.",
                "A crocodile cannot stick its tongue out.",
                "The fastest guitarist played 'Flight of the Bumblebee' at 1,600 beats per minute.",
                "A snail can sleep for up to three years.",
                "The Earth is not a perfect sphere — it bulges at the equator.",
                "Butterflies taste with their feet.",
                "A shrimp's heart is in its head.",
                "The longest recorded flight of a chicken is 13 seconds.",
                "Starfish have no brains.",
                "An ostrich's eye is bigger than its brain.",
                "A blue whale's tongue can weigh as much as an elephant.",
                "The Amazon River once flowed backwards.",
                "Mount Everest grows about 4 mm taller every year.",
                "There are more trees on Earth than stars in the Milky Way.",
                "Your stomach gets a new lining every few days to avoid digesting itself.",
                "Some turtles can breathe through their bottoms.",
                "A group of pugs is called a grumble.",
                "Peanuts are an ingredient in dynamite.",
                "A hippo can run faster than a human.",
                "The inventor of the Pringles can is buried in one.",
                "Hot peppers trick your brain into thinking your mouth is being burned.",
                "A 'moonquake' can last much longer than an earthquake.",
                "Copper kills bacteria on contact within hours.",
                "The word 'set' has more meanings than any other English word.",
                "Tigers have striped skin, not just striped fur.",
                "A group of crows is called a murder.",
                "Nintendo was founded in 1889 as a playing card company.",
                "The longest word without a vowel is 'rhythms'.",
                "Humans share about 60% of their DNA with bananas.",
                "A day on Mercury lasts two of its years.",
                "Most lipstick contains fish scales.",
                "An adult lion's roar can be heard 8 km away.",
                "The first item ever sold on eBay was a broken laser pointer.",
                "A chameleon's tongue can be twice the length of its body.",
                "Oysters can change gender multiple times during their lives.",
                "Sharks are older than Saturn's rings.",
                "It rains diamonds on Neptune and Uranus.",
                "A rabbit can sleep with its eyes open.",
                "More people are killed each year by vending machines than by sharks.",
                "The Guinness World Record for the longest concert was 453 hours.",
                "Alpacas hum to communicate.",
                "The world's quietest room is so silent you can hear your own heartbeat.",
                "Air travel is statistically safer than being struck by lightning.",
                "A woodpecker's tongue wraps around its skull to protect its brain.",
                "Your brain uses about 20% of your body's energy.",
                "Honeybees can recognise human faces.",
                "One quarter of all your bones are in your feet.",
                "The average cloud weighs around 500,000 kg.",
                "Fingernails grow about 3.5 mm per month.",
                "Maine is the closest US state to Africa.",
                "A Boeing 747's wingspan is longer than the Wright brothers' first flight.",
                "The average person walks the equivalent of five times around the world in a lifetime.",
                "It's impossible to tickle yourself.",
                "Earthworms have five 'hearts' called aortic arches.",
                "Saturn is so light it would float in water.",
                "Polar bears have black skin under their white fur.",
                "A hippopotamus can hold its breath for about five minutes underwater.",
                "The fastest land animal is the cheetah, reaching 112 km/h.",
                "Human nose prints are as unique as fingerprints.",
                "A giraffe's neck has the same number of vertebrae as a human's — seven.",
                "The average person spends six months of their life waiting for red lights.",
                "The dot on a dice is called a 'pip'.",
                "Nutmeg can be toxic in large doses.",
                "Cherries are members of the rose family.",
                "A mole can dig a tunnel 90 metres long in one night.",
                "Your left lung is smaller than your right to make room for your heart.",
                "A full moon is about 400,000 times dimmer than the Sun.",
                "The average human brain contains about 86 billion neurons.",
                "A day on Mars is called a 'sol' and lasts 24 hours and 37 minutes.",
            ];
            echo json_encode($facts);
        ?>;

        const lever = document.getElementById("lever");
        const factText = document.getElementById("fact-text");
        const FACT_HISTORY_KEY = "funFactHistory";
        const HISTORY_LIMIT = 50;
        let current = -1;
        let pulling = false;

        function getHistory() {
            try {
                const stored = JSON.parse(localStorage.getItem(FACT_HISTORY_KEY));
                return Array.isArray(stored) ? stored : [];
            } catch (e) {
                return [];
            }
        }

        function nextFactIndex() {
            let history = getHistory();
            let candidates = facts.map((_, i) => i).filter(i => !history.includes(i));
            if (candidates.length === 0) {
                history = [];
                candidates = facts.map((_, i) => i);
            }
            const pick = candidates[Math.floor(Math.random() * candidates.length)];
            history.push(pick);
            if (history.length > HISTORY_LIMIT) {
                history = history.slice(-HISTORY_LIMIT);
            }
            try {
                localStorage.setItem(FACT_HISTORY_KEY, JSON.stringify(history));
            } catch (e) {
            }
            return pick;
        }

        lever.addEventListener("click", () => {
            if (pulling) return;
            pulling = true;
            lever.classList.add("pulled");

            setTimeout(() => {
                current = nextFactIndex();

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

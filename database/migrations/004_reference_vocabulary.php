<?php
return function(PDO $pdo): void {
    foreach([['Vitamin A','mcg RAE'],['Vitamin C','mg'],['Vitamin B1 (Thiamine)','mg'],['Vitamin B9 (Folate)','mcg'],['Vitamin E','mg'],['Vitamin K','mcg']] as [$name,$unit]) {
        $stmt=$pdo->prepare('INSERT INTO VITAMIN(name,unit) SELECT ?,? WHERE NOT EXISTS(SELECT 1 FROM VITAMIN WHERE name=?)');$stmt->execute([$name,$unit,$name]);
    }
    foreach(['Raw/Fresh','Boiling','Steaming','Frying'] as $method) $pdo->prepare('INSERT INTO COOKING_METHOD(name) SELECT ? WHERE NOT EXISTS(SELECT 1 FROM COOKING_METHOD WHERE name=?)')->execute([$method,$method]);
    $stmt=$pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='NUTRIENT_RETENTION' AND COLUMN_NAME='preparation_notes'");
    if(!$stmt->fetchColumn()) $pdo->exec('ALTER TABLE NUTRIENT_RETENTION ADD preparation_notes TEXT NULL');
};

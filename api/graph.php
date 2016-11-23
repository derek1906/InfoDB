<?php
	function findPersonNetwork($personID) {
		$root = $_SERVER['DOCUMENT_ROOT'];
		include $root."/internal/commons.php";

		$db = new DBAccess();
		$query = new DBQuery($db);

		/*
			movieIDs:
			SELECT cinematographyId
			FROM Involving
			WHERE $personID = celebrityId
		*/

		/*
			SELECT c.firstName, c.lastName, c.id, m.id, m.title, m.productionYear, m.type
			FROM Involving i, Celebrity c, Cinematography m
			WHERE i.cinematographyId = m.id AND i.celebrityId = c.id AND c.id != $personID AND m.id IN movieIDs
			ORDER BY c.id
		*/
		$query->group = new DBGroup([
			new DBSelect(["c.firstName", "c.lastName", "c.id", "m.id", "m.title", "m.productionYear", "m.type"]),
			new DBFrom(["Involving i, Celebrity c, Cinematography m"]),
			new DBWhere(["i.cinematographyId = m.id", "i.celebrityId = c.id", "c.id != ?", 
				"m.id IN (SELECT cinematographyId FROM Involving WHERE celebrityId = ?)"], 
				[[$personID, "i"], [$personID, "i"]]),
			new DBOrder(["c.id"])
		]);

		$result = $query->query();

		$nameQuery = new DBQuery($db);
		$nameQuery->group = new DBGroup([
			new DBSelect(["CONCAT(firstName, ' ', lastName)"]),
			new DBFrom(["Celebrity"]),
			new DBWhere(["id = ?"], [[$personID, "i"]])
		]);
		$name = $nameQuery->query();

		if(!$result || !$name){
			returnStatus(400);
		}
		
		$network = ["targetName" => $name[0][0], "nodes" => []];
		foreach($result as $row) {
			if (end($network["nodes"])) {
				$node = &$network["nodes"][key($network["nodes"])];
			}
			else {
				$node = false;
			}
			if (!$node || $node["id"] != $row[2]) {
				$network["nodes"][] = ["display" => $row[0] . " " . $row[1], "id" => $row[2], "cinematographies" => []];
				end($network["nodes"]);
				$node = &$network["nodes"][key($network["nodes"])];
			}
			$node["cinematographies"][] = ["id" => $row[3], "title" => $row[4], "year" => $row[5], "type" => $row[6]];
		}
		
		return json_encode($network);
	}

	echo findPersonNetwork((int) $_GET["id"]);
?>


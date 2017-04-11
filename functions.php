<?php
    $conn = oci_connect($username = 'cgfussel', $password = 'Economy321', $connection_string = '//oracle.cise.ufl.edu/orcl');

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 

    //Get needed variables from Javascript
    $check = $_GET['check'];
    $var1 = $_GET['var1'];
    $var2 = $_GET['var2'];
    $var3 = $_GET['var3'];
    $var4 = $_GET['var4'];

    //Results stored in $data
    $data = '';

    //Queries stored in $q
    $q = '';

    //Fill in 'Area' dropdown based on Area Type
    if($check == 'areatype') {
        //$var1 is the name of the state we are using
        $q = "SELECT distinct communities.name 
                FROM Communities, States 
                WHERE communities.belongsTo = states.stateid 
                    AND states.name = '" . $var1 . "'";
        $stid = oci_parse($conn, $q); //. $var1);
        oci_define_by_name($stid, 'NAME', $name);
        oci_execute($stid);

        $data = '<li role="presentation"><a role="menuitem" tabindex="-1" href="#"><b>' . $var1 . '</b></a></li>';
        while(oci_fetch($stid)) {
            $data = $data . '<li role="presentation"><a role="menuitem" tabindex="-1" href="#">' . $name . '</a></li>';
        }
        echo $data; 
    } //Add Data when "Add Series" is selected
    else if($check == 'add') { 
        //$var1 is the data series
        //$var2 is area type (state or community)
        //$var3 is the specific state or community 
        //$var4 is the year we're looking at
        if($var1 == 'Age') {
            //ADD DIFFERENT QUERY
            $q = "select Median(person.Agep)
                    from person join household on (person.serialNo = household.serialNo and  person.year = household.year)
                    join communities on (PUMA = communityID and communities.year = household.year)
                    where communities.name = '" . $var3 . "' and person.year = " . $var4 . " group by communities.name";
            $stid = oci_parse($conn, $q);
            oci_define_by_name($stid, 'MEDIAN(PERSON.AGEP)', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Fertility Rate') {
            //ADD DIFFERENT QUERY
            $q = "select sum(household.NOC)/(Count(*) / 2)
                    from person join household on (person.serialNo = household.serialNo and person.year = household.year)
                    join communities on (PUMA = communityID and communities.year = household.year)
                    where communities.name = '" . $var3 . "' and household.year = '" . $var4 . "' group by communities.name";
            $stid = oci_parse($conn, $q);
            oci_define_by_name($stid, 'SUM(HOUSEHOLD.NOC)/(COUNT(*)/2)', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Income') {
            // Actually economic growth = Avg income MEDIAN INCOME query not working
            $q = "select Avg(isum)
                    from (select sum (income.wagp) as isum from income join household on (income.serialNo = household.serialNo and income.year = household.year) 
                    join communities on (household.PUMA = communities.communityID and household.year = communities.year)
                    where income.year = " . $var4 . " and communities.name = '" . $var3 . "' 
                    group by communities.name, household.serialNo), communities
                    where communities.name = '" . $var3 ."' and communities.year = " . $var4 . " group by communities.name";
            $stid = oci_parse($conn, $q);
            oci_define_by_name($stid, 'AVG(ISUM)', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Industry') {
            //ADD DIFFERENT QUERY
            $q = "SELECT distinct name 
                    FROM '" . $var2;
            $stid = oci_parse($conn, $q);  
            oci_define_by_name($stid, 'NAME', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Mobility Status') {
            // actually number of migrants
            $q = "select Count(mig)
                    from (person join household on (person.serialNo = household.serialNo and person.year = household.year)) join communities on 
                    (household.PUMA = communities.communityID and household.year= communities.year)
                    where communities.name = '" . $var3 . "' and person.year = " . $var4 . " and mig = 2
                    group by communities.name";
            $stid = oci_parse($conn, $q);  
            oci_define_by_name($stid, 'COUNT(MIG)', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Poverty Rate') {
            // Poverty query not working
            $q = "SELECT distinct name 
                    FROM '" . $var2;
            $stid = oci_parse($conn, $q);  
            oci_define_by_name($stid, 'NAME', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Primary Language') {
            // Actually number of languages...I think. Maybe should be most widely spoken language?
            $q = "select Count(distinct person.LANP)
                    from person join HOUSEHOLD on (person.serialNo = household.serialNo and person.year = household.year)
                    join communities on (household.PUMA = communities.communityID and household.year = communities.year)
                    where communities.name = '" . $var3 . "' and person.year = " . $var4 . " group by communities.name";
            $stid = oci_parse($conn, $q);  
            oci_define_by_name($stid, 'COUNT(DISTINCTPERSON.LANP)', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Property Value') {
            //ADD DIFFERENT QUERY
            $q = "select Avg(coalesce(household.VALP, 0))
                    from household join communities on (PUMA = communityID and communities.year = household.year) 
                    where communities.name = '" . $var3 . "' and household.year = " . $var4 . " and household.NP != 0 group by communities.name";
            $stid = oci_parse($conn, $q);  
            oci_define_by_name($stid, 'AVG(COALESCE(HOUSEHOLD.VALP,0))', $name);
            oci_execute($stid);
        }
        else if($var1 == 'Race') {
            // Maybe remove entirely, I dunno. No race queries.
            $q = "SELECT distinct name 
                    FROM '" . $var2;
            $stid = oci_parse($conn, $q);  
            oci_define_by_name($stid, 'NAME', $name);
            oci_execute($stid);
        }
        
        while(oci_fetch($stid)) {
            $data = $name;
        }
        if(is_null($data)) {
            $data = "null";
        }
        echo $data;
    }
    else if($check == 'compare') {
        //$var1 is the first area to compare
        //$var2 is the second area to compare
        //$var3 is the first data series to compare
        //$var4 is the second data series to compare
        $q = "SELECT distinct name 
                    FROM States";
            $stid = oci_parse($conn, $q);  
            oci_define_by_name($stid, 'NAME', $name);
            oci_execute($stid);

        while(oci_fetch($stid)) {
            $data = $name;
        }
        echo $data;
    }


    // Close the Oracle connection
    oci_free_statement($stid);
    oci_close($conn);
?>
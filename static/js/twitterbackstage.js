$(document).ready(function() {

$("#backstageBox").prepend('<div id="tweet"></div>');

$("#tweet").hover(function(){

    $("#tweet").animate({
    height: '86px'
  }, 300);
    $("#tweet #etc").hide();

},function(){

    $("#tweet").animate({
    height: '16px'
  }, 300);
    $("#tweet #etc").show();
});

    // set your twitter id
    var user = 'amenpunktde';
      
    // using jquery built in get json method with twitter api, return only one result
    $.getJSON('http://twitter.com/statuses/user_timeline.json?screen_name=' + user + '&count=1&callback=?', function(data)      {
          
        // result returned
        var tweet = data[0].text;
        var date = data[0].created_at;
        datum = new Date(date);

        var tweetdatecomp = datum.getDate()+"-"+datum.getMonth();
        var jetzt = new Date();
        var today = jetzt.getDate()+"-"+jetzt.getMonth();
        var gestern = new Date(jetzt.getTime()-86400000);
        var yesterday = gestern.getDate()+"-"+gestern.getMonth();

        if(jetzt.getTime() - 120000 < datum.getTime())
                   var tweetdate = "vor " + Math.round((jetzt.getTime() - datum.getTime()) / 1000) + " Sekunden";
        else if(jetzt.getTime() - 60000*120 < datum.getTime())
                   var tweetdate = "vor " + Math.round((jetzt.getTime() - datum.getTime()) / 60000) + " Minuten";
/*        else if(jetzt.getTime() - 60000*60*12 < datum.getTime())
                   var tweetdate = "vor " + Math.round((jetzt.getTime() - datum.getTime()) / 60000 / 60) + " Stunden";
*/
        else if(tweetdatecomp == today)
                   var tweetdate = "heute";
        else if(tweetdatecomp == yesterday && (jetzt.getTime() - 60000*60*10) < datum.getTime())
                   var tweetdate = "gestern, "+datum.getHours()+":"+datum.getMinutes()+" Uhr";
        else 
                   var tweetdate = "";


        
if(tweetdate!=""){
      
        // process links and reply
        tweet = tweet.replace(/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig, function(url) {
            return '<a href="'+url+'" target="_blank">' + url + '</a>';
        }).replace(/B@([_a-z0-9]+)/ig, function(reply) {
            return  reply.charAt(0)+'<a href="http://twitter.com/'+reply.substring%281%29+'">'+reply.substring(1)+'</a>';
        });

       // Kram davor und danach      
       tweet = '<div id="etc">&nbsp;...</div><img width="16" style="border:none;margin-right:0.1em" src="http://www.jesus.de/fileadmin/jesusde/redaktion/Standards/twitter_newbird_blue.png"><b>' + tweetdate + ':</b> ' + data[0].text + '<p style="font-size:10px;text-align:right;padding-top:5px"><a href="http://www.twitter.com/jdebackstage" target="_blank" class="bubsi"><b>Alle Beiträge</b></a></p>';

        // output the result
        $("#tweet").html(tweet).slideDown();
}
    });
      
});
#!/usr/bin/perl -w 
use strict; 
use warnings; 

my $file = $ARGV[0]; 
my $FILE; 

open ($FILE, $file); 


my %media; 


foreach my $line (<$FILE>){
    next if $line =~ /^$/; 
    chomp $line;
    $line =~ s/.wav//g;  
    my $name = $line;
    my ($class, $blah) = split /-/, $name; 
    $media{$class}{$name} = 1;  

    print "$class $name\n";
}


my $xmlfile = "snippit-spandsp.xml";
my $XML; 
open($XML, ">$xmlfile"); 

my $mediafreq = 800; 

print $XML "    <!-- Make-Busy -->\n"; 
for my $class (sort keys %media){
    my $classname = uc $class; 
    print $XML "    <!-- Make-Busy $classname -->\n"; 
    #print $XML "    <descriptor name=\"$classname\">\n";
    foreach my $name (sort keys %{$media{$class}}){
  	print "generating $mediafreq hz tone for file $name.wav\n";
	print `sox -n -r 8000 prompts/$name.wav synth 3 sin $mediafreq vol 0.88`;
	`sox -n -r 8000  prompts/$name.wav synth 3 sin $mediafreq vol 0.88`;
        print $XML "    <descriptor name=\"". uc $name . "\">\n";
	print $XML "        <tone name=\"$name\">\n";
	print $XML "            <element freq1=\"$mediafreq\" freq2=\"0\" min=\"600\" max=\"0\"/>\n";
        print $XML "        </tone>\n"; 
        print $XML "    </descriptor>\n";
        $mediafreq += 50; 
    }
    #reset media freq to 1000 (new frequencies are expensive!) 
    $mediafreq = 800; 
    #print $XML "    </descriptor>\n";
}


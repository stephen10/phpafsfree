#!/usr/bin/perl -w
$ID = q$Id: partinfo,v 1.2 2004/03/08 23:26:43 eagle Exp $;
#
# partinfo -- Show summary of space on AFS server partitions.
#
# Written by Neil Crellin <neilc@stanford.edu>
# Modifications by Russ Allbery <rra@stanford.edu>
# Copyright 1998, 1999, 2003, 2004
#     Board of Trustees, Leland Stanford Jr. University
# Minor mods (HTML colors, vos locations, formatting)
#              by Stephen Joyce, Oct 2006
# 2008-03-22 Added quick and dirty code to display the number of volumes
# on each partition. - Stephen Joyce
#
# Takes an AFS server as an argument and then runs vos partinfo on it,
# parsing the output and producing a slightly more verbose report that
# includes percent full on each partition and how much more data can be
# added to that partition before putting it over 85% full.
#

##############################################################################
# Site configuration
##############################################################################

# The default thresholds.  The first gives the threshold before the partition
# is considered reasonably well-utilized, and defaults to 60%.  The second
# gives the threshold before the partition is considered full (and also
# determines how much space is shown as available in the last column of the
# report).  It defaults to 85%.  These are used for colorizing the output and
# can be overridden with the -T command-line option.
$THRESH_EMPTY = 60;
$THRESH_FULL  = 85;

# The full path to vos.  Allow for Linux where the preferred location may be
# on local disk.
($VOS) = grep { -x $_ } qw( /usr/sbin/vos /usr/bin/vos /usr/afsws/etc/vos /usr/local/bin/vos /usr/bin/vos );
$VOS ||= 'vos';

# You may also want to tweak the width of the partition column in the output
# if you use machine names of a different length.  Search for %17s below.

##############################################################################
# Modules and declarations
##############################################################################

use strict;
use vars qw($ID $THRESH_EMPTY $THRESH_FULL $VOS);

use Getopt::Long qw(GetOptions);

# Term::ANSIColor is also loaded dynamically if color is requested with the -c
# option.

##############################################################################
# Output formatting
##############################################################################

# Given the percentage of free space on a partition, return the color string
# to use for that partition.  Uses the $THRESH_* global variables, which can
# be overridden with -T.
sub color {
    my ($pfree) = @_;
    if    ($pfree < $THRESH_EMPTY) { return "<font color=\"green\">" }
    elsif ($pfree > $THRESH_FULL)  { return "<font color=\"red\">"   }
    else { return "<font color=\"black\">" }
}

# Print the heading for the output.
sub heading {
    printf "%17s: %10s %10s %10s %8s %10s %10s\n",
        'Partition', 'Total GB', 'Used GB', 'Free GB', '%Full', 'Avail GB', 'Num Vols';
}

# Given the partition tag, the total space, the free space, and a flag saying
# whether to use color, output one line of formatted output.  We can't easily
# use formats because they mess up when colors are used.
sub show {
    my ($partition, $total, $free, $color, $numvols) = @_;
    my $used = $total - $free;
    my $usable = int ($free - ((100 - $THRESH_FULL) / 100) * $total);
    my $pfree = 100 * $used / $total;
    my $cstart = $color ? color ($pfree) : '';
    my $cend = $color ? "</font>" : '';
    printf "%17s: %10.2f %10.2f %10.2f %s%7.2f%%%s %10.2f %5s\n",
        $partition, $total, $used, $free, $cstart, $pfree, $cend, $usable, $numvols;
}

##############################################################################
# Main routine
##############################################################################

# Parse our options.
my $fullpath = $0;
$0 =~ s%.*/%%;
my ($color, $help, $quiet, $thresholds, $totals, $version);
Getopt::Long::config ('bundling', 'no_ignore_case');
GetOptions ('color|c'        => \$color,
            'help|h'         => \$help,
            'quiet|q'        => \$quiet,
            'thresholds|T=s' => \$thresholds,
            'total|t'        => \$totals,
            'version|v'      => \$version) or exit 1;
if ($help) {
    print "Feeding myself to perldoc, please wait....\n";
    exec ('perldoc', '-t', $fullpath);
} elsif ($version) {
    my $version = join (' ', (split (' ', $ID))[1..3]);
    $version =~ s/,v\b//;
    $version =~ s/(\S+)$/($1)/;
    $version =~ tr%/%-%;
    print $version, "\n";
    exit;
}
require Term::ANSIColor if $color;
die "Usage: $0 [-chqtv] [-T <empty>,<full>] <afssvr>\n" if (@ARGV != 1);
my $server = shift;
$server = 'afssvr' . $server if ($server =~ /^\d+$/);

# Process threshold argument if provided.
if ($thresholds) {
    die "$0: argument to -T must be two numbers 0-100 separated by a comma\n"
        unless ($thresholds =~ /^(\d{1,2}|100),(\d{1,2}|100)$/);
    ($THRESH_EMPTY, $THRESH_FULL) = ($1, $2);
}

# Run vos partinfo and parse the output.  Print out a line for each partition,
# and copy any other output to standard output without changing it.
print "$server\n" unless $quiet;
heading unless $quiet;
my ($ttotal, $tfree);
my ($numvols, $tnumvols);
open (PARTINFO, "$VOS partinfo $server |") or die "$0: can't fork: $!\n";
while (<PARTINFO>) {
    if (m%^Free space on partition (/vicep\S+): (\d+) K .* total (\d+)$%) {
        my ($partition, $free, $total) = ($1, $2, $3);
        #$partition = "$server $partition";
	$total=$total/1000000;
	$free=$free/1000000;
	$numvols=0;
	open (VOLINFO, "$VOS listvldb -server $server -part $1|");
	  while (<VOLINFO>) {
	  chomp;
	  my $line=$_;
	    #if ($line=~/RWrite/) { $numvols++; }
	    #if ($line=~/ROnly/) { $numvols++; }
	    if ($line=~/Total entries/) {
	      $numvols=(split /\s+/,$line)[2];
	    }
	  }
	close(VOLINFO);
        show ($partition, $total, $free, $color, $numvols);
        if ($totals) {
            $ttotal += $total;
            $tfree += $free;
	    $tnumvols += $numvols;
        }
    } else {
        print;
    }
}
close PARTINFO;

# Print totals if we're supposed to.
if ($totals) {
    print "\n";
    show ('TOTAL', $ttotal, $tfree, $color, $tnumvols);
}

__END__

############################################################################
# Documentation
############################################################################

=head1 NAME

partinfo - Show summary of space on AFS server partitions

=head1 SYNOPSIS

partinfo [B<-chqtv>] [-T <empty>,<full>] I<afssvr>

=head1 DESCRIPTION

B<partinfo> does a vos partinfo on a particular AFS server to determine the
amount of used and free space.  Unlike vos partinfo, however, it also
formats the output into a more easily readable tabular form, displays the
total disk space, the used disk space, and the free disk space, calculates
what percent full the partition is, and displays the amount that can still
be put on the partition before it goes over a particular threshold.

Normally, B<partinfo> displays a header above the output giving the meaning
of the columns, but this can optionally be suppressed.  B<partinfo> can also
optionally use color to highlight partitions with plenty of free space and
partitions that are too full.

There are two thresholds that B<partinfo> cares about.  The first is the
threshold before which the partition will be considered to be mostly empty.
This will only change the output if color is requested with B<-c>; if it is,
the partition will be shown in green.  It defaults to 60%.  The second is
the threshold after which the partition will be considered full.  The final
column, available space, is the amount of space remaining on the partition
before it goes over this threshold, and partitions over this threshold will
be shown in red if color is requested with B<-c>.  The thresholds may be
changed at the top of this script or overridden for one invocation with
B<-T>.

If the server given is just a number, C<afssvr> will be prepended to form
the server name.

=head1 OPTIONS

=over 4

=item B<-c>, B<--color>

Use color to highlight interesting data.  Currently this just means that the
percent full column will be shown in green for partitions under 60% full and
in red for partitions over 85% full.  Using this option requires that the
Term::ANSIColor module be installed and available on the user's system (this
module is not required if B<-c> is not used).

To override the above thresholds, see the B<-T> option.

=item B<-h>, B<--help>

Print out this documentation (which is done simply by feeding the script to
C<perldoc -t>).

=item B<-q>, B<--quiet>

Suppress the header normally printed to explain the meanings of each column
of data.

=item B<-t>, B<--total>

Print totals for the entire server.

=item B<-T> I<empty>,I<full>

=item B<--thresholds>=I<empty>,I<full>

Override the default thresholds of 60% (before which a partition will be
considered mostly empty) and 85% (after which the partition will be
considered full).  B<-T> should take two integers between 0 and 100
separated by a comma.

=item B<-v>, B<--version>

Print out the version of B<partinfo> and exit.

=back

=head1 EXAMPLES

The following command shows the current status of afssvr1:

    partinfo afssvr1

This command shows the same data, but without the header and with color
highlighting of interesting percent full data:

    partinfo -qc afssvr1

This command does the same for afssvr5:

    partinfo --color --quiet afssvr5

Use thresholds of 70% and 90% instead, showing the results in color:

    partinfo -T 70,90 -c afssvr5

=head1 SEE ALSO

L<vos(1)>, L<vos_partinfo(1)>

L<http://www.eyrie.org/~eagle/software/partinfo/> will have the current
version of this program.

=head1 AUTHORS

Original Perl script written by Neil Crellin <neilc@stanford.edu>, modified
by Russ Allbery <rra@stanford.edu> to use formats, to include an explanatory
header, to use color if wanted, and to add optional totals.

=head1 COPYRIGHT AND LICENSE

Copyright 1998, 1999, 2003, 2004 Board of Trustees, Leland Stanford
Jr. University.

This program is free software; you may redistribute it and/or modify it
under the same terms as Perl itself.

=cut


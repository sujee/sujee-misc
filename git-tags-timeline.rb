#!/usr/bin/env ruby

require 'date'

## prints git tags in chronological order (earliest first)
## doesn't do much error checking (assumes 'happy path')
## Usage:
##    from a git repository run
##       ruby git-tags-timeline.rb


# TagInfo class.
# parses a 'git show <tag>' output and figures out various things

class TagInfo
  attr_accessor :tag, :taginfo, :tag_type, :tagger, :tagdate, :commit, :commit_author, :commit_date, :commit_comment

  def initialize(tagname)
    @tag = tagname
    @taginfo = `git show #{@tag}`

    lines = @taginfo.split("\n")
    #puts lines

    i = 0
    # if the first line starts with 'tag' then it is an 'annotated' tag
    # if it starts with 'commit' then it is a lightweight tag
    
    #puts "lines[#{i}]: #{lines[i]}"
    
    if lines[i].start_with? 'tag '
      @tag_type = 'annotated'
    else
      @tag_type = 'lightweight'
    end


    if lines[i].start_with? 'commit '
      words = lines[i].split('commit')
      #p words
      @commit = words[1].strip  if words.length == 2
    end

    # tagger
    i += 1
    #puts "lines[#{i}]: #{lines[i]}"
    if lines[i].start_with? 'Tagger: '
      words = lines[i].split('Tagger:')
      #p words
      @tagger = words[1].strip  if words.length == 2
    end

    # date
    i += 1
    #puts "lines[#{i}]: #{lines[i]}"
    if lines[i].start_with? 'Date: '
      words = lines[i].split('Date:')
      #p words
      @tagdate = DateTime.parse(words[1].strip) if words.length == 2
    end

    # if we don't have commit yet, go find it
    unless @commit
      while (! lines[i].start_with? 'commit ') do
        #puts "lines[#{i}]: #{lines[i]}"
        i += 1
      end
      if lines[i].start_with? 'commit '
        words = lines[i].split('commit')
        #p words
        @commit = words[1].strip  if words.length == 2
      end
    end
  end

  def to_s
    s = "tag: #{@tag},  tagger: #{@tagger},  tag date: #{@tagdate.strftime('%Y-%m-%d:%H-%M-%S')},  commit: #{@commit},  tag type: #{@tag_type}"
  end

end

## -------------- start MAIN ---------

tags = []

tagscmd=`git tag`
#puts tagscmd

gittags = tagscmd.split.map{|t| t.strip}
#puts gittags

gittags.each do |t|
  t = TagInfo.new(t)
  tags << t
  #p t
  #puts "\n"
end


# sort by date
tags.sort! do |a,b|
  a.tagdate <=> b.tagdate
end

# display sorted tags
tags.each {|t| puts t}

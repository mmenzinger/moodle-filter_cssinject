# moodle_filter_cssinject

This simple filter allowes users to add css to their content, without switching to code-view. It also provides pre-made css-classes to visually enhance basic text areas.

## Usage examples

Use `[!box: ... !]` to directly apply a buildin bordered css-style around the content. There are several buildin classes that can also be used. All buildin classes can be combined and are internally prefixed with `cssinject_box_` to prevent collisions.
```
[!box: info red !]
```
Available classes (more to come!):
```
// prebuild classes
info
read

// icons
lamp
book

// colors
gray
red
green
blue
yellow
```


Use `[!style: ... !]` to directly apply css-styles to a new div around the content. 
```
[!style: color:red; font-weight: bold; !]
```

Use `[!class: ... !]` to directly apply css-classes to a new div around the content.
```
[!class: my_awesome_class !]
```

Use `[!page: ... !]` to directly add pure css to the whole page. It gets injected using a style element.
```
[!page: 
  * {
    font-family: "Times New Roman", Times, serif;
  }
!]
```
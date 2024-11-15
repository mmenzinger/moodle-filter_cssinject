# moodle_filter_cssinject

This simple filter allowes users to add css to their content, without switching to code-view. It also provides pre-made css-classes to visually enhance basic text areas.

## Usage examples

Use `[!style: ... !]` to directly apply css-styles to a new div around the content. 
```
[!style: color:red; font-weight: bold; !]
```

Use `[!class: ... !]` to directly apply css-classes to a new div around the content. There are several buildin classes that can also be used [TODO].
```
[!class: info green !]
```

Use `[!page: ... !]` to directly add pure css to the whole page. It gets injected using a style element.
```
[!page: 
  * {
    font-family: "Times New Roman", Times, serif;
  }
!]
```

## Buildin Classes

[TODO]